<?php

namespace App\Exceptions;

use Exception;

class LimitExceededException extends Exception
{
    public function __construct(
        public readonly string $limitType,
        public readonly int $currentValue,
        public readonly int $maxValue,
        public readonly ?string $planCode = null,
        string $message = '',
    ) {
        parent::__construct($message ?: "Limit exceeded: {$limitType} ({$currentValue}/{$maxValue})");
    }

    public function toArray(): array
    {
        return [
            'code' => 'limit_exceeded',
            'message' => $this->getMessage(),
            'details' => [
                'limit_type' => $this->limitType,
                'current' => $this->currentValue,
                'max' => $this->maxValue,
                'plan' => $this->planCode,
            ],
        ];
    }
}
