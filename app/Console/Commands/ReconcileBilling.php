<?php

namespace App\Console\Commands;

use App\Models\CommandRun;
use App\Models\PaddleWebhookEvent;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ReconcileBilling extends Command
{
    protected $signature = 'billing:reconcile {--dry-run}';

    protected $description = 'Process unhandled Paddle webhook events into subscription records';

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
            $events = PaddleWebhookEvent::whereNull('processed_at')->get();

            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would process {$events->count()} webhook events.");

                $commandRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'records_processed' => 0,
                    'output' => "Dry run: {$events->count()} events found",
                ]);

                return self::SUCCESS;
            }

            $processed = 0;
            $failed = 0;

            foreach ($events as $event) {
                try {
                    $this->processEvent($event);
                    $event->update(['processed_at' => now()]);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $event->update(['processing_error' => $e->getMessage()]);
                    $this->error("Event {$event->id} failed: {$e->getMessage()}");
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

    protected function processEvent(PaddleWebhookEvent $event): void
    {
        $payload = $event->payload ?? [];
        $subscriptionId = $payload['subscription_id'] ?? $payload['paddle_subscription_id'] ?? null;

        match ($event->event_type) {
            'subscription.created' => $this->handleCreated($payload, $subscriptionId),
            'subscription.updated' => $this->handleUpdated($payload, $subscriptionId),
            'subscription.cancelled' => $this->handleCancelled($subscriptionId),
            'subscription.paused' => $this->handlePaused($subscriptionId),
            default => null,
        };
    }

    protected function handleCreated(array $payload, ?string $subscriptionId): void
    {
        Subscription::updateOrCreate(
            ['paddle_subscription_id' => $subscriptionId],
            [
                'organization_id' => $payload['organization_id'] ?? null,
                'plan_id' => $payload['plan_id'] ?? null,
                'paddle_customer_id' => $payload['customer_id'] ?? null,
                'status' => $payload['status'] ?? 'active',
                'current_period_start' => $payload['current_period_start'] ?? null,
                'current_period_end' => $payload['current_period_end'] ?? null,
            ]
        );
    }

    protected function handleUpdated(array $payload, ?string $subscriptionId): void
    {
        $subscription = Subscription::where('paddle_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => $payload['status'] ?? $subscription->status,
                'plan_id' => $payload['plan_id'] ?? $subscription->plan_id,
                'current_period_start' => $payload['current_period_start'] ?? $subscription->current_period_start,
                'current_period_end' => $payload['current_period_end'] ?? $subscription->current_period_end,
            ]);
        }
    }

    protected function handleCancelled(?string $subscriptionId): void
    {
        $subscription = Subscription::where('paddle_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }
    }

    protected function handlePaused(?string $subscriptionId): void
    {
        $subscription = Subscription::where('paddle_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update(['status' => 'paused']);
        }
    }
}
