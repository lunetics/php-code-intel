<?php

declare(strict_types=1);

namespace CodeIntel\Error;

/**
 * Defines severity levels for errors
 */
enum ErrorSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    /**
     * Get numeric priority for severity level (higher = more severe)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
        };
    }

    /**
     * Get color for console output
     */
    public function getColor(): string
    {
        return match($this) {
            self::INFO => 'info',
            self::WARNING => 'comment',
            self::ERROR => 'error',
            self::CRITICAL => 'error',
        };
    }

    /**
     * Check if this severity level is at least as severe as another
     */
    public function isAtLeast(self $other): bool
    {
        return $this->getPriority() >= $other->getPriority();
    }
}