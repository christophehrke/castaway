<?php

namespace App\Console\Commands;

use App\Models\AiIntent;
use App\Models\CommandRun;
use App\Models\PipelineError;
use App\Models\Recording;
use App\Models\UsageCounter;
use App\Services\WorkflowGenerationService;
use Illuminate\Console\Command;

class GenerateWorkflows extends Command
{
    protected $signature = 'workflows:generate {--limit=50} {--recording-id=} {--dry-run}';

    protected $description = 'Generate workflows from intent-ready recordings';

    public function __construct(private WorkflowGenerationService $workflowService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $commandRun = CommandRun::create([
            'command' => $this->getName(),
            'arguments' => $this->options(),
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        try {
            $query = Recording::where('status', 'intent_ready');

            if ($recordingId = $this->option('recording-id')) {
                $query->where('id', $recordingId);
            }

            $recordings = $query->limit((int) $this->option('limit'))->get();

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would generate workflows for {$recordings->count()} recordings.");

                $commandRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'records_processed' => 0,
                    'output' => "Dry run: {$recordings->count()} recordings found",
                ]);

                return self::SUCCESS;
            }

            $processed = 0;
            $failed = 0;

            foreach ($recordings as $recording) {
                try {
                    $intent = AiIntent::where('recording_id', $recording->id)
                        ->where('status', 'completed')
                        ->orderByDesc('version')
                        ->first();

                    if (!$intent) {
                        $this->warn("No completed intent for recording {$recording->id}, skipping.");
                        continue;
                    }

                    $this->workflowService->generate($recording, $intent);

                    $counter = UsageCounter::forCurrentPeriod($recording->organization_id);
                    $counter->increment('conversions_count');

                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;

                    PipelineError::create([
                        'recording_id' => $recording->id,
                        'stage' => 'workflow_generation',
                        'error_code' => 'COMMAND_GENERATION_FAILED',
                        'error_message' => $e->getMessage(),
                        'error_context' => ['trace' => substr($e->getTraceAsString(), 0, 2000)],
                        'created_at' => now(),
                    ]);

                    $this->error("Recording {$recording->id} failed: {$e->getMessage()}");
                }
            }

            $this->info("Generated: {$processed}, Failed: {$failed}");

            $commandRun->update([
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'records_processed' => $processed,
                'records_failed' => $failed,
                'output' => "Generated: {$processed}, Failed: {$failed}",
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");

            $commandRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'records_failed' => 1,
                'output' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
