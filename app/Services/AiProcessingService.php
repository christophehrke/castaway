<?php

namespace App\Services;

use App\Models\AiIntent;
use App\Models\PipelineError;
use App\Models\Recording;
use App\Models\RecordingAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class AiProcessingService
{
    /**
     * Process a recording through AI pipeline: STT → Vision → Fusion → Intent.
     * Updates recording status: processing_ai → intent_ready (or failed).
     */
    public function process(Recording $recording): void
    {
        $recording->update(['status' => 'processing_ai']);

        try {
            $currentVersion = AiIntent::where('recording_id', $recording->id)->max('version') ?? 0;
            $newVersion = $currentVersion + 1;

            $intent = AiIntent::create([
                'recording_id' => $recording->id,
                'version' => $newVersion,
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            $transcript = $this->transcribe($recording);
            $visionData = $this->analyzeFrames($recording);
            $intentData = $this->fuseIntent($recording, $transcript, $visionData);
            $validatedIntent = $this->validateIntent($intentData);

            $intent->update([
                'status' => 'completed',
                'title' => $validatedIntent['title'],
                'description' => $validatedIntent['description'],
                'steps' => $validatedIntent['steps'],
                'raw_transcript' => json_encode($transcript),
                'raw_vision_data' => $visionData,
                'model_used' => 'gpt-4',
                'processing_completed_at' => now(),
            ]);

            $recording->update(['status' => 'intent_ready']);
        } catch (\Throwable $e) {
            $recording->update(['status' => 'failed']);

            if (isset($intent)) {
                $intent->update(['status' => 'failed']);
            }

            PipelineError::create([
                'recording_id' => $recording->id,
                'stage' => 'ai_processing',
                'error_code' => 'AI_PROCESSING_FAILED',
                'error_message' => $e->getMessage(),
                'error_context' => ['trace' => substr($e->getTraceAsString(), 0, 2000)],
                'created_at' => now(),
            ]);

            Log::error('AI processing failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Speech-to-text using OpenAI Whisper API.
     * Returns timestamped transcript segments.
     */
    protected function transcribe(Recording $recording): array
    {
        $audioAsset = RecordingAsset::where('recording_id', $recording->id)
            ->where('type', 'audio')
            ->first();

        if (!$audioAsset) {
            throw new \RuntimeException('No audio asset found for recording ' . $recording->id);
        }

        $audioPath = Storage::path($audioAsset->storage_path);

        $response = OpenAI::audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($audioPath, 'r'),
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['segment'],
        ]);

        $segments = [];
        foreach ($response->segments ?? [] as $segment) {
            $segments[] = [
                'start' => $this->formatTimestamp($segment->start),
                'end' => $this->formatTimestamp($segment->end),
                'text' => $segment->text,
            ];
        }

        return $segments;
    }

    /**
     * Vision analysis on extracted frames using OpenAI Vision API.
     * Returns UI element descriptions per frame.
     */
    protected function analyzeFrames(Recording $recording): array
    {
        $frameAssets = RecordingAsset::where('recording_id', $recording->id)
            ->where('type', 'frame')
            ->orderBy('created_at')
            ->get();

        if ($frameAssets->isEmpty()) {
            return [];
        }

        $visionResults = [];

        foreach ($frameAssets as $asset) {
            $framePath = Storage::path($asset->storage_path);
            $imageData = base64_encode(file_get_contents($framePath));

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a UI analysis assistant. Describe the UI elements, applications, and actions visible in this screenshot. Focus on identifying: application names, buttons clicked, form fields, URLs, menu items, and any workflow-related actions.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/jpeg;base64,{$imageData}",
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Describe the UI elements and actions visible in this frame.',
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 500,
            ]);

            $visionResults[] = [
                'frame_number' => $asset->metadata['frame_number'] ?? null,
                'timestamp_seconds' => $asset->metadata['timestamp_seconds'] ?? null,
                'description' => $response->choices[0]->message->content,
            ];
        }

        return $visionResults;
    }

    /**
     * Fusion: combine transcript + vision data into structured Intent Model.
     * Uses GPT-4 to generate JSON matching the intent schema.
     */
    protected function fuseIntent(Recording $recording, array $transcript, array $visionData): array
    {
        $systemPrompt = <<<'PROMPT'
You are an automation workflow analyst. Given a transcript of a screen recording and vision analysis of key frames, generate a structured intent model that describes the workflow steps the user is performing.

Output ONLY valid JSON matching this schema:
{
    "title": "string - concise workflow title",
    "description": "string - what this workflow automates",
    "steps": [
        {
            "order": 1,
            "action": "string (one of: http_request, send_email, spreadsheet_read, spreadsheet_write, database_query, send_slack_message, webhook_trigger, code_execute, if_condition, set_variable)",
            "app": "string (e.g., 'Google Sheets', 'Gmail', 'HTTP')",
            "description": "string - what this step does",
            "parameters": { "key": "value" },
            "evidence": {
                "transcript_start": "HH:MM:SS",
                "transcript_end": "HH:MM:SS",
                "frame_numbers": [1, 2]
            }
        }
    ]
}
PROMPT;

        $userMessage = "TRANSCRIPT:\n" . json_encode($transcript, JSON_PRETTY_PRINT)
            . "\n\nVISION ANALYSIS:\n" . json_encode($visionData, JSON_PRETTY_PRINT);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.2,
            'max_tokens' => 4000,
        ]);

        $content = $response->choices[0]->message->content;

        // Extract JSON from response (handle markdown code blocks)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse intent JSON from AI response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Validate intent JSON against expected schema.
     * Returns validated intent or throws exception.
     */
    protected function validateIntent(array $intentData): array
    {
        if (empty($intentData['title']) || !is_string($intentData['title'])) {
            throw new \RuntimeException('Intent validation failed: missing or invalid title');
        }

        if (empty($intentData['description']) || !is_string($intentData['description'])) {
            throw new \RuntimeException('Intent validation failed: missing or invalid description');
        }

        if (!isset($intentData['steps']) || !is_array($intentData['steps'])) {
            throw new \RuntimeException('Intent validation failed: missing or invalid steps');
        }

        foreach ($intentData['steps'] as $index => $step) {
            if (!isset($step['order']) || !is_int($step['order'])) {
                throw new \RuntimeException("Intent validation failed: step {$index} missing order");
            }
            if (empty($step['action']) || !is_string($step['action'])) {
                throw new \RuntimeException("Intent validation failed: step {$index} missing action");
            }
            if (empty($step['app']) || !is_string($step['app'])) {
                throw new \RuntimeException("Intent validation failed: step {$index} missing app");
            }
            if (empty($step['description']) || !is_string($step['description'])) {
                throw new \RuntimeException("Intent validation failed: step {$index} missing description");
            }
        }

        return $intentData;
    }

    /**
     * Format seconds to HH:MM:SS timestamp.
     */
    protected function formatTimestamp(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
