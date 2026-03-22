<?php

namespace App\Services;

use App\Models\PipelineError;
use App\Models\Recording;
use App\Models\RecordingAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class MediaProcessingService
{
    /**
     * Process a recording's media: normalize video, extract audio, extract frames, generate thumbnail.
     * Updates recording status through: processing_media → media_ready (or failed).
     */
    public function process(Recording $recording): void
    {
        $recording->update(['status' => 'processing_media']);

        try {
            $normalizedPath = $this->normalizeVideo($recording);
            $this->extractAudio($normalizedPath, $recording);
            $this->extractFrames($normalizedPath, $recording);
            $this->generateThumbnail($normalizedPath, $recording);

            $duration = $this->getDuration($normalizedPath);
            if ($duration !== null) {
                $recording->update(['duration_seconds' => $duration]);
            }

            $usageLimitService = app(UsageLimitService::class);
            $usageLimitService->canProcessDuration($recording->organization, $duration);

            $recording->update(['status' => 'media_ready']);
        } catch (\Throwable $e) {
            $recording->update(['status' => 'failed']);

            PipelineError::create([
                'recording_id' => $recording->id,
                'stage' => 'media_processing',
                'error_code' => 'MEDIA_PROCESSING_FAILED',
                'error_message' => $e->getMessage(),
                'error_context' => ['trace' => substr($e->getTraceAsString(), 0, 2000)],
                'created_at' => now(),
            ]);

            Log::error('Media processing failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalize video to standardized MP4 (H.264, AAC, 720p max).
     */
    protected function normalizeVideo(Recording $recording): string
    {
        $inputPath = Storage::path($recording->storage_path);
        $dir = "recordings/{$recording->organization_id}/{$recording->id}";
        $outputRelative = "{$dir}/normalized.mp4";
        $outputPath = Storage::path($outputRelative);

        Storage::makeDirectory($dir);

        $process = new Process([
            'ffmpeg', '-i', $inputPath,
            '-vf', 'scale=-2:720',
            '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k',
            '-y', $outputPath,
        ]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Video normalization failed: ' . $process->getErrorOutput());
        }

        RecordingAsset::create([
            'recording_id' => $recording->id,
            'type' => 'normalized_video',
            'storage_path' => $outputRelative,
            'mime_type' => 'video/mp4',
            'metadata' => ['codec' => 'h264', 'resolution' => '720p'],
            'created_at' => now(),
        ]);

        return $outputPath;
    }

    /**
     * Extract audio as WAV for STT.
     */
    protected function extractAudio(string $normalizedPath, Recording $recording): string
    {
        $dir = "recordings/{$recording->organization_id}/{$recording->id}";
        $outputRelative = "{$dir}/audio.wav";
        $outputPath = Storage::path($outputRelative);

        $process = new Process([
            'ffmpeg', '-i', $normalizedPath,
            '-vn', '-acodec', 'pcm_s16le', '-ar', '16000', '-ac', '1',
            '-y', $outputPath,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Audio extraction failed: ' . $process->getErrorOutput());
        }

        RecordingAsset::create([
            'recording_id' => $recording->id,
            'type' => 'audio',
            'storage_path' => $outputRelative,
            'mime_type' => 'audio/wav',
            'metadata' => ['sample_rate' => 16000, 'channels' => 1],
            'created_at' => now(),
        ]);

        return $outputPath;
    }

    /**
     * Extract key frames (1 frame per 5 seconds).
     */
    protected function extractFrames(string $normalizedPath, Recording $recording): array
    {
        $dir = "recordings/{$recording->organization_id}/{$recording->id}/frames";
        $outputRelative = "recordings/{$recording->organization_id}/{$recording->id}/frames/frame_%04d.jpg";
        $outputPath = Storage::path($outputRelative);

        Storage::makeDirectory($dir);

        $process = new Process([
            'ffmpeg', '-i', $normalizedPath,
            '-vf', 'fps=1/5',
            '-y', $outputPath,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Frame extraction failed: ' . $process->getErrorOutput());
        }

        $frames = [];
        $frameFiles = glob(Storage::path($dir) . '/frame_*.jpg');
        sort($frameFiles);

        foreach ($frameFiles as $index => $framePath) {
            $relPath = "{$dir}/frame_" . sprintf('%04d', $index + 1) . '.jpg';
            $asset = RecordingAsset::create([
                'recording_id' => $recording->id,
                'type' => 'frame',
                'storage_path' => $relPath,
                'mime_type' => 'image/jpeg',
                'metadata' => ['frame_number' => $index + 1, 'timestamp_seconds' => ($index) * 5],
                'created_at' => now(),
            ]);
            $frames[] = $asset;
        }

        return $frames;
    }

    /**
     * Generate thumbnail from first frame.
     */
    protected function generateThumbnail(string $normalizedPath, Recording $recording): string
    {
        $dir = "recordings/{$recording->organization_id}/{$recording->id}";
        $outputRelative = "{$dir}/thumbnail.jpg";
        $outputPath = Storage::path($outputRelative);

        $process = new Process([
            'ffmpeg', '-i', $normalizedPath,
            '-vframes', '1', '-q:v', '2',
            '-y', $outputPath,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Thumbnail generation failed: ' . $process->getErrorOutput());
        }

        RecordingAsset::create([
            'recording_id' => $recording->id,
            'type' => 'thumbnail',
            'storage_path' => $outputRelative,
            'mime_type' => 'image/jpeg',
            'metadata' => [],
            'created_at' => now(),
        ]);

        return $outputPath;
    }

    /**
     * Get video duration using ffprobe.
     */
    public function getDuration(string $filePath): ?float
    {
        $process = new Process([
            'ffprobe', '-v', 'quiet',
            '-print_format', 'json',
            '-show_format', $filePath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $data = json_decode($process->getOutput(), true);

        return isset($data['format']['duration'])
            ? (float) $data['format']['duration']
            : null;
    }
}
