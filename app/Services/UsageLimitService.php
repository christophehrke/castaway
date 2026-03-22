<?php

namespace App\Services;

use App\Exceptions\LimitExceededException;
use App\Models\Organization;
use App\Models\UsageCounter;

class UsageLimitService
{
    protected const DEFAULT_FREE_LIMITS = [
        'max_recordings_per_month' => 3,
        'max_minutes_per_recording' => 5,
        'max_storage_gb' => 1,
        'max_seats' => 1,
    ];

    /**
     * Check if organization can upload a new recording.
     * Returns true if within limits, throws LimitExceededException if not.
     */
    public function canUploadRecording(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        $usage = $this->getCurrentUsage($organization);
        $max = $limits['max_recordings_per_month'] ?? self::DEFAULT_FREE_LIMITS['max_recordings_per_month'];
        $current = $usage['recordings_count'];

        if ($current >= $max) {
            $planCode = $this->getActivePlanCode($organization);
            throw new LimitExceededException(
                limitType: 'recordings_per_month',
                currentValue: $current,
                maxValue: $max,
                planCode: $planCode,
                message: "You've reached your " . ($planCode ?? 'free') . " plan recording limit ({$current}/{$max}).",
            );
        }

        return true;
    }

    /**
     * Check if a recording duration is within plan limits.
     */
    public function canProcessDuration(Organization $organization, float $durationSeconds): bool
    {
        $limits = $this->getPlanLimits($organization);
        $maxMinutes = $limits['max_minutes_per_recording'] ?? self::DEFAULT_FREE_LIMITS['max_minutes_per_recording'];
        $durationMinutes = (int) ceil($durationSeconds / 60);

        if ($durationMinutes > $maxMinutes) {
            throw new LimitExceededException(
                limitType: 'minutes_per_recording',
                currentValue: $durationMinutes,
                maxValue: $maxMinutes,
                planCode: $this->getActivePlanCode($organization),
                message: "Recording duration ({$durationMinutes}min) exceeds your plan limit ({$maxMinutes}min).",
            );
        }

        return true;
    }

    /**
     * Check if organization can generate more workflows (conversions).
     */
    public function canGenerateWorkflow(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        $usage = $this->getCurrentUsage($organization);
        $max = $limits['max_conversions_per_month'] ?? PHP_INT_MAX;
        $current = $usage['conversions_count'];

        if ($current >= $max) {
            throw new LimitExceededException(
                limitType: 'conversions_per_month',
                currentValue: $current,
                maxValue: $max,
                planCode: $this->getActivePlanCode($organization),
                message: "You've reached your conversion limit ({$current}/{$max}).",
            );
        }

        return true;
    }

    /**
     * Get the organization's current plan limits.
     * Returns the plan's limits array, or default free limits if no subscription.
     */
    public function getPlanLimits(Organization $organization): array
    {
        $subscription = $organization->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan')
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return self::DEFAULT_FREE_LIMITS;
        }

        return array_merge(self::DEFAULT_FREE_LIMITS, $subscription->plan->limits ?? []);
    }

    /**
     * Get current usage for the organization this period.
     */
    public function getCurrentUsage(Organization $organization): array
    {
        $counter = UsageCounter::forCurrentPeriod($organization->id);

        return [
            'recordings_count' => (int) $counter->recordings_count,
            'conversions_count' => (int) $counter->conversions_count,
            'storage_bytes' => (int) $counter->storage_bytes,
        ];
    }

    /**
     * Get remaining quota info for display.
     */
    public function getRemainingQuota(Organization $organization): array
    {
        $limits = $this->getPlanLimits($organization);
        $usage = $this->getCurrentUsage($organization);

        $maxRecordings = $limits['max_recordings_per_month'] ?? self::DEFAULT_FREE_LIMITS['max_recordings_per_month'];
        $maxConversions = $limits['max_conversions_per_month'] ?? PHP_INT_MAX;
        $maxStorageGb = $limits['max_storage_gb'] ?? self::DEFAULT_FREE_LIMITS['max_storage_gb'];

        return [
            'recordings' => max(0, $maxRecordings - $usage['recordings_count']),
            'conversions' => $maxConversions === PHP_INT_MAX ? null : max(0, $maxConversions - $usage['conversions_count']),
            'storage_gb' => round(max(0, $maxStorageGb - ($usage['storage_bytes'] / 1_073_741_824)), 2),
        ];
    }

    protected function getActivePlanCode(Organization $organization): ?string
    {
        $subscription = $organization->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan')
            ->latest()
            ->first();

        return $subscription?->plan?->code;
    }
}
