<?php

declare(strict_types=1);

namespace CodeIntel\Error;

/**
 * Stores detailed context information for errors
 */
readonly class ErrorContext
{
    /**
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public string $filePath,
        public ErrorCategory $category,
        public ErrorSeverity $severity,
        public string $message,
        public ?\Throwable $exception = null,
        public ?int $lineNumber = null,
        public ?int $columnNumber = null,
        public ?string $codeSnippet = null,
        public ?float $memoryUsage = null,
        public ?float $executionTime = null,
        public array $additionalData = []
    ) {}

    /**
     * Create error context from a throwable
     * 
     * @param array<string, mixed> $additionalData
     */
    public static function fromThrowable(
        string $filePath,
        ErrorCategory $category,
        \Throwable $exception,
        array $additionalData = []
    ): self {
        return new self(
            filePath: $filePath,
            category: $category,
            severity: $category->getSeverity(),
            message: $exception->getMessage(),
            exception: $exception,
            lineNumber: $exception->getLine(),
            additionalData: $additionalData
        );
    }

    /**
     * Create error context for syntax errors
     */
    public static function syntaxError(
        string $filePath,
        string $message,
        ?int $lineNumber = null,
        ?string $codeSnippet = null
    ): self {
        return new self(
            filePath: $filePath,
            category: ErrorCategory::SYNTAX_ERROR,
            severity: ErrorSeverity::WARNING,
            message: $message,
            lineNumber: $lineNumber,
            codeSnippet: $codeSnippet
        );
    }

    /**
     * Create error context for I/O errors
     */
    public static function ioError(string $filePath, string $message, ?\Throwable $exception = null): self
    {
        return new self(
            filePath: $filePath,
            category: ErrorCategory::IO_ERROR,
            severity: ErrorSeverity::ERROR,
            message: $message,
            exception: $exception
        );
    }

    /**
     * Create error context for memory errors
     */
    public static function memoryError(
        string $filePath,
        string $message,
        float $memoryUsage,
        ?\Throwable $exception = null
    ): self {
        return new self(
            filePath: $filePath,
            category: ErrorCategory::MEMORY_ERROR,
            severity: ErrorSeverity::CRITICAL,
            message: $message,
            exception: $exception,
            memoryUsage: $memoryUsage
        );
    }

    /**
     * Create error context for timeout errors
     */
    public static function timeoutError(
        string $filePath,
        string $message,
        float $executionTime
    ): self {
        return new self(
            filePath: $filePath,
            category: ErrorCategory::TIMEOUT_ERROR,
            severity: ErrorSeverity::ERROR,
            message: $message,
            executionTime: $executionTime
        );
    }

    /**
     * Get formatted error message with context
     */
    public function getFormattedMessage(): string
    {
        $parts = [$this->message];

        if ($this->lineNumber !== null) {
            $parts[] = sprintf('(line %d)', $this->lineNumber);
        }

        if ($this->memoryUsage !== null) {
            $parts[] = sprintf('(memory: %.2fMB)', $this->memoryUsage / 1024 / 1024);
        }

        if ($this->executionTime !== null) {
            $parts[] = sprintf('(time: %.2fs)', $this->executionTime);
        }

        return implode(' ', $parts);
    }

    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filePath' => $this->filePath,
            'category' => $this->category->value,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'lineNumber' => $this->lineNumber,
            'columnNumber' => $this->columnNumber,
            'codeSnippet' => $this->codeSnippet,
            'memoryUsage' => $this->memoryUsage,
            'executionTime' => $this->executionTime,
            'exceptionClass' => $this->exception !== null ? $this->exception::class : null,
            'exceptionMessage' => $this->exception?->getMessage(),
            'additionalData' => $this->additionalData,
        ];
    }
}