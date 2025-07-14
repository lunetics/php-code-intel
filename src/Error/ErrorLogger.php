<?php

declare(strict_types=1);

namespace CodeIntel\Error;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * PSR-3 compatible error logger for code intelligence operations
 */
class ErrorLogger
{
    /** @var array<ErrorContext> */
    private array $errors = [];
    
    /** @var array<string, int> */
    private array $errorCounts = [];
    
    private int $maxErrors;
    private ErrorSeverity $minSeverity;

    public function __construct(
        int $maxErrors = 1000,
        ErrorSeverity $minSeverity = ErrorSeverity::INFO
    ) {
        $this->maxErrors = $maxErrors;
        $this->minSeverity = $minSeverity;
    }

    /**
     * Log an error context
     */
    public function log(ErrorContext $context): void
    {
        // Only log if severity meets minimum threshold
        if (!$context->severity->isAtLeast($this->minSeverity)) {
            return;
        }

        // Prevent memory issues by limiting stored errors
        if (count($this->errors) >= $this->maxErrors) {
            array_shift($this->errors); // Remove oldest error
        }

        $this->errors[] = $context;
        
        // Update category counts
        $category = $context->category;
        $categoryKey = $category->value;
        $this->errorCounts[$categoryKey] = ($this->errorCounts[$categoryKey] ?? 0) + 1;
    }

    /**
     * Log a parse error
     */
    public function logParseError(
        string $filePath,
        \PhpParser\Error $error,
        ?string $codeSnippet = null
    ): void {
        $context = ErrorContext::fromThrowable(
            filePath: $filePath,
            category: ErrorCategory::PARSER_ERROR,
            exception: $error,
            additionalData: ['codeSnippet' => $codeSnippet]
        );
        
        $this->log($context);
    }

    /**
     * Log a syntax error
     */
    public function logSyntaxError(
        string $filePath,
        string $message,
        ?int $lineNumber = null,
        ?string $codeSnippet = null
    ): void {
        $context = ErrorContext::syntaxError($filePath, $message, $lineNumber, $codeSnippet);
        $this->log($context);
    }

    /**
     * Log an I/O error
     */
    public function logIoError(string $filePath, string $message, ?\Throwable $exception = null): void
    {
        $context = ErrorContext::ioError($filePath, $message, $exception);
        $this->log($context);
    }

    /**
     * Log a memory error
     */
    public function logMemoryError(
        string $filePath,
        string $message,
        ?int $memoryUsage = null,
        ?\Throwable $exception = null
    ): void {
        $memoryUsage = $memoryUsage ?? memory_get_usage(true);
        $context = ErrorContext::memoryError($filePath, $message, (float)$memoryUsage, $exception);
        $this->log($context);
    }

    /**
     * Log a timeout error
     */
    public function logTimeoutError(string $filePath, string $message, float $executionTime): void
    {
        $context = ErrorContext::timeoutError($filePath, $message, $executionTime);
        $this->log($context);
    }

    /**
     * Get all logged errors
     * 
     * @return array<ErrorContext>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors by category
     * 
     * @return array<ErrorContext>
     */
    public function getErrorsByCategory(ErrorCategory $category): array
    {
        return array_filter($this->errors, fn($error) => $error->category === $category);
    }

    /**
     * Get errors by severity
     * 
     * @return array<ErrorContext>
     */
    public function getErrorsBySeverity(ErrorSeverity $severity): array
    {
        return array_filter($this->errors, fn($error) => $error->severity === $severity);
    }

    /**
     * Get error count by category
     */
    public function getErrorCount(?ErrorCategory $category = null): int
    {
        if ($category === null) {
            return count($this->errors);
        }
        
        return $this->errorCounts[$category->value] ?? 0;
    }

    /**
     * Get error summary statistics
     * 
     * @return array<string, mixed>
     */
    public function getErrorSummary(): array
    {
        $summary = [
            'total' => count($this->errors),
            'byCategory' => [],
            'bySeverity' => [],
            'criticalFiles' => [],
        ];

        foreach (ErrorCategory::cases() as $category) {
            $count = $this->getErrorCount($category);
            if ($count > 0) {
                $summary['byCategory'][$category->value] = $count;
            }
        }

        foreach (ErrorSeverity::cases() as $severity) {
            $count = count($this->getErrorsBySeverity($severity));
            if ($count > 0) {
                $summary['bySeverity'][$severity->value] = $count;
            }
        }

        // Find files with multiple errors
        $fileErrorCounts = [];
        foreach ($this->errors as $error) {
            $fileErrorCounts[$error->filePath] = ($fileErrorCounts[$error->filePath] ?? 0) + 1;
        }
        
        $criticalFiles = array_filter($fileErrorCounts, fn($count) => $count >= 3);
        arsort($criticalFiles);
        $summary['criticalFiles'] = array_slice($criticalFiles, 0, 10, true);

        return $summary;
    }

    /**
     * Display error summary using Symfony console
     */
    public function displaySummary(SymfonyStyle $io): void
    {
        $summary = $this->getErrorSummary();
        
        if ($summary['total'] === 0) {
            $io->success('No errors encountered during processing');
            return;
        }

        $io->section(sprintf('Error Summary (%d total)', $summary['total']));

        // Display by category
        if (!empty($summary['byCategory']) && is_array($summary['byCategory'])) {
            $io->text('By Category:');
            foreach ($summary['byCategory'] as $category => $count) {
                if (is_string($category) && is_int($count)) {
                    $categoryEnum = ErrorCategory::from($category);
                    $io->text(sprintf('  • %s: %d (%s)', 
                        $categoryEnum->getDescription(), 
                        $count, 
                        $categoryEnum->getSeverity()->value
                    ));
                }
            }
        }

        // Display by severity
        if (!empty($summary['bySeverity']) && is_array($summary['bySeverity'])) {
            $io->text('By Severity:');
            foreach ($summary['bySeverity'] as $severity => $count) {
                if (is_string($severity) && is_int($count)) {
                    $severityEnum = ErrorSeverity::from($severity);
                    $io->text(sprintf('  • %s: %d', ucfirst($severity), $count));
                }
            }
        }

        // Display problematic files
        if (!empty($summary['criticalFiles']) && is_array($summary['criticalFiles'])) {
            $io->text('Files with Multiple Errors:');
            foreach ($summary['criticalFiles'] as $file => $count) {
                if (is_string($file) && is_int($count)) {
                    $io->text(sprintf('  • %s (%d errors)', basename($file), $count));
                }
            }
        }
    }

    /**
     * Check if error rate exceeds threshold
     */
    public function hasExceededErrorThreshold(int $totalFiles, float $threshold = 0.2): bool
    {
        if ($totalFiles === 0) {
            return false;
        }

        $errorFiles = count(array_unique(array_map(fn($error) => $error->filePath, $this->errors)));
        return ($errorFiles / $totalFiles) > $threshold;
    }

    /**
     * Clear all logged errors
     */
    public function clear(): void
    {
        $this->errors = [];
        $this->errorCounts = [];
    }

    /**
     * Check if any critical errors have been logged
     */
    public function hasCriticalErrors(): bool
    {
        return count($this->getErrorsBySeverity(ErrorSeverity::CRITICAL)) > 0;
    }

    /**
     * Export errors to array for serialization
     * 
     * @return array<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn($error) => $error->toArray(), $this->errors);
    }
}