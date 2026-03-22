<?php

namespace App\Console\Commands;

use App\Models\CommandRun;
use App\Models\Plan;
use Illuminate\Console\Command;

class SyncPlans extends Command
{
    protected $signature = 'plans:sync';

    protected $description = 'Upsert the default plans into the plans table';

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
            $plans = [
                [
                    'code' => 'starter',
                    'name' => 'Starter',
                    'description' => 'For individuals getting started',
                    'price_monthly_cents' => 2900,
                    'price_yearly_cents' => 29000,
                    'limits' => [
                        'max_recordings_per_month' => 20,
                        'max_minutes_per_recording' => 10,
                        'max_storage_gb' => 5,
                        'max_seats' => 1,
                        'max_conversions_per_month' => 20,
                    ],
                    'is_active' => true,
                    'sort_order' => 1,
                ],
                [
                    'code' => 'pro',
                    'name' => 'Pro',
                    'description' => 'For growing teams',
                    'price_monthly_cents' => 7900,
                    'price_yearly_cents' => 79000,
                    'limits' => [
                        'max_recordings_per_month' => 100,
                        'max_minutes_per_recording' => 30,
                        'max_storage_gb' => 50,
                        'max_seats' => 5,
                        'max_conversions_per_month' => 100,
                    ],
                    'is_active' => true,
                    'sort_order' => 2,
                ],
                [
                    'code' => 'enterprise',
                    'name' => 'Enterprise',
                    'description' => 'For large organizations',
                    'price_monthly_cents' => 24900,
                    'price_yearly_cents' => 249000,
                    'limits' => [
                        'max_recordings_per_month' => 500,
                        'max_minutes_per_recording' => 60,
                        'max_storage_gb' => 500,
                        'max_seats' => 25,
                        'max_conversions_per_month' => 500,
                    ],
                    'is_active' => true,
                    'sort_order' => 3,
                ],
            ];

            $created = 0;
            $updated = 0;

            foreach ($plans as $planData) {
                $code = $planData['code'];
                unset($planData['code']);

                $plan = Plan::where('code', $code)->first();

                if ($plan) {
                    $plan->update($planData);
                    $updated++;
                } else {
                    Plan::create(array_merge(['code' => $code], $planData));
                    $created++;
                }
            }

            $this->info("Plans synced: {$created} created, {$updated} updated.");

            $commandRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'records_processed' => $created + $updated,
                'output' => "{$created} created, {$updated} updated",
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
