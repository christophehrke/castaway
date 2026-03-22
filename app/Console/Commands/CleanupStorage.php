<?php

namespace App\Console\Commands;

use App\Models\CommandRun;
use App\Models\Recording;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupStorage extends Command
{
    protected $signature = 'recordings:cleanup-storage {--days=30} {--dry-run}';

    protected $description = 'Clean up soft-deleted recordings and their physical files';

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
            $days = (int) $this->option('days');
            $cutoff = now()->subDays($days);

            $recordings = Recording::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->get();

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would clean up {$recordings->count()} recordings.");

                $commandRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'records_processed' => 0,
                    'output' => "Dry run: {$recordings->count()} recordings found",
                ]);

                return self::SUCCESS;
            }

            $cleaned = 0;
            $bytesFreed = 0;

            foreach ($recordings as $recording) {
                try {
                    $bytesFreed += (int) $recording->file_size_bytes;

                    // Delete physical file
                    if ($recording->storage_path && Storage::exists($recording->storage_path)) {
                        Storage::delete($recording->storage_path);
                    }

                    // Delete asset files and records
                    foreach ($recording->assets as $asset) {
                        if ($asset->storage_path && Storage::exists($asset->storage_path)) {
                            Storage::delete($asset->storage_path);
                        }
                        $asset->delete();
                    }

                    // Force delete the recording
                    $recording->forceDelete();
                    $cleaned++;
                } catch (\Throwable $e) {
                    $this->error("Recording {$recording->id} cleanup failed: {$e->getMessage()}");
                }
            }

            $mbFreed = round($bytesFreed / 1024 / 1024, 2);
            $this->info("Cleaned up: {$cleaned} recordings, {$mbFreed} MB freed.");

            $commandRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'records_processed' => $cleaned,
                'output' => "Cleaned: {$cleaned} recordings, {$bytesFreed} bytes freed",
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
