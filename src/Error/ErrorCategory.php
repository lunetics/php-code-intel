<?php

declare(strict_types=1);

namespace CodeIntel\Error;

/**
 * Categorizes different types of errors that can occur during code intelligence operations
 */
enum ErrorCategory: string
{
    case SYNTAX_ERROR = 'syntax';
    case IO_ERROR = 'io';
    case MEMORY_ERROR = 'memory';
    case TIMEOUT_ERROR = 'timeout';
    case PARSER_ERROR = 'parser';
    case CONFIGURATION_ERROR = 'configuration';
    case INDEX_ERROR = 'index';

    /**
     * Get human-readable description of the error category
     */
    public function getDescription(): string
    {
        return match($this) {
            self::SYNTAX_ERROR => 'PHP syntax error in file',
            self::IO_ERROR => 'File system I/O error',
            self::MEMORY_ERROR => 'Memory limit or allocation error',
            self::TIMEOUT_ERROR => 'Operation timeout exceeded',
            self::PARSER_ERROR => 'PHP-Parser processing error',
            self::CONFIGURATION_ERROR => 'Configuration or validation error',
            self::INDEX_ERROR => 'Symbol index operation error',
        };
    }

    /**
     * Get severity level for this error category
     */
    public function getSeverity(): ErrorSeverity
    {
        return match($this) {
            self::SYNTAX_ERROR => ErrorSeverity::WARNING,
            self::IO_ERROR => ErrorSeverity::ERROR,
            self::MEMORY_ERROR => ErrorSeverity::CRITICAL,
            self::TIMEOUT_ERROR => ErrorSeverity::ERROR,
            self::PARSER_ERROR => ErrorSeverity::WARNING,
            self::CONFIGURATION_ERROR => ErrorSeverity::ERROR,
            self::INDEX_ERROR => ErrorSeverity::ERROR,
        };
    }

    /**
     * Determine if this error category should stop processing
     */
    public function shouldStopProcessing(): bool
    {
        return match($this) {
            self::SYNTAX_ERROR => false,  // Continue with other files
            self::IO_ERROR => false,      // Continue with other files
            self::MEMORY_ERROR => true,   // Stop to prevent system issues
            self::TIMEOUT_ERROR => false, // Continue with other files
            self::PARSER_ERROR => false,  // Continue with other files
            self::CONFIGURATION_ERROR => true, // Stop until config is fixed
            self::INDEX_ERROR => false,   // Continue with other files
        };
    }
}