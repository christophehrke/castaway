<?php

namespace App\Console\Commands;

use App\Models\AiIntent;
use App\Models\CommandRun;
use App\Models\Organization;
use App\Models\Recording;
use App\Models\UsageCounter;
use App\Models\Workflow;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateStats extends Command
{
    protected $signature = 'stats:aggregate {--period=} {--dry-run}';

    protected $description = 'Aggregate usage statistics per organization for a given period';

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
            $period = $this->option('period') ?: now()->format('Y-m');
            $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $organizations = Organization::all();

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would aggregate stats for {$organizations->count()} organizations, period: {$period}.");

                $commandRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'records_processed' => 0,
                    'output' => "Dry run: {$organizations->count()} organizations, period: {$period}",
                ]);

                return self::SUCCESS;
            }

            $processed = 0;

            foreach ($organizations as $org) {
                $recordingsCount = Recording::where('organization_id', $org->id)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->count();

                $conversionsCount = Workflow::where('organization_id', $org->id)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->count();

                $storageBytes = Recording::where('organization_id', $org->id)
                    ->sum('file_size_bytes');

                $aiTokensUsed = AiIntent::whereHas('recording', function ($q) use ($org) {
                    $q->where('organization_id', $org->id);
                })
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('tokens_used');

                UsageCounter::updateOrCreate(
                    ['organization_id' => $org->id, 'period' => $period],
                    [
                        'recordings_count' => $recordingsCount,
                        'conversions_count' => $conversionsCount,
                        'storage_bytes' => $storageBytes,
                        'ai_tokens_used' => $aiTokensUsed,
                    ]
                );

                $processed++;
            }

            $this->info("Aggregated stats for {$processed} organizations, period: {$period}.");

            $commandRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'records_processed' => $processed,
                'output' => "Aggregated {$processed} organizations for {$period}",
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
