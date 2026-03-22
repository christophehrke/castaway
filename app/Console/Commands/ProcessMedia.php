<?php

namespace App\Console\Commands;

use App\Models\CommandRun;
use App\Models\PipelineError;
use App\Models\Recording;
use App\Services\MediaProcessingService;
use Illuminate\Console\Command;

class ProcessMedia extends Command
{
    protected $signature = 'recordings:process-media {--limit=50} {--recording-id=} {--dry-run}';

    protected $description = 'Process uploaded recordings through media pipeline';

    public function __construct(private MediaProcessingService $mediaService)
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
            $query = Recording::where('status', 'uploaded');

            if ($recordingId = $this->option('recording-id')) {
                $query->where('id', $recordingId);
            }

            $recordings = $query->limit((int) $this->option('limit'))->get();

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would process {$recordings->count()} recordings.");

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
                    $this->mediaService->process($recording);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;

                    PipelineError::create([
                        'recording_id' => $recording->id,
                        'stage' => 'media_processing',
                        'error_code' => 'COMMAND_PROCESSING_FAILED',
                        'error_message' => $e->getMessage(),
                        'error_context' => ['trace' => substr($e->getTraceAsString(), 0, 2000)],
                        'created_at' => now(),
                    ]);

                    $this->error("Recording {$recording->id} failed: {$e->getMessage()}");
                }
            }

            $this->info("Processed: {$processed}, Failed: {$failed}");

            $commandRun->update([
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'records_processed' => $processed,
                'records_failed' => $failed,
                'output' => "Processed: {$processed}, Failed: {$failed}",
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
