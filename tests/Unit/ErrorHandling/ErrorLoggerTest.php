<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit\ErrorHandling;

use CodeIntel\Error\ErrorCategory;
use CodeIntel\Error\ErrorContext;
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorSeverity;
use PHPUnit\Framework\TestCase;
use PhpParser\Error as ParseError;

/**
 * Tests for ErrorLogger class
 */
class ErrorLoggerTest extends TestCase
{
    private ErrorLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new ErrorLogger();
    }

    public function test_log_stores_error_context(): void
    {
        $context = ErrorContext::syntaxError(
            '/test/file.php',
            'Unexpected token',
            10,
            'invalid syntax here'
        );

        $this->logger->log($context);

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame($context, $errors[0]);
    }

    public function test_log_parse_error(): void
    {
        $parseError = new ParseError('Unexpected token T_STRING');
        
        $this->logger->logParseError('/test/file.php', $parseError, 'invalid code');

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        
        $error = $errors[0];
        $this->assertEquals('/test/file.php', $error->filePath);
        $this->assertEquals(ErrorCategory::PARSER_ERROR, $error->category);
        $this->assertStringContainsString('Unexpected token T_STRING', $error->message);
        $this->assertEquals('invalid code', $error->additionalData['codeSnippet']);
    }

    public function test_log_syntax_error(): void
    {
        $this->logger->logSyntaxError('/test/file.php', 'Missing semicolon', 15, 'echo "test"');

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        
        $error = $errors[0];
        $this->assertEquals('/test/file.php', $error->filePath);
        $this->assertEquals(ErrorCategory::SYNTAX_ERROR, $error->category);
        $this->assertEquals(ErrorSeverity::WARNING, $error->severity);
        $this->assertEquals('Missing semicolon', $error->message);
        $this->assertEquals(15, $error->lineNumber);
        $this->assertEquals('echo "test"', $error->codeSnippet);
    }

    public function test_log_io_error(): void
    {
        $exception = new \RuntimeException('File not found');
        
        $this->logger->logIoError('/test/missing.php', 'Cannot read file', $exception);

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        
        $error = $errors[0];
        $this->assertEquals('/test/missing.php', $error->filePath);
        $this->assertEquals(ErrorCategory::IO_ERROR, $error->category);
        $this->assertEquals(ErrorSeverity::ERROR, $error->severity);
        $this->assertEquals('Cannot read file', $error->message);
        $this->assertSame($exception, $error->exception);
    }

    public function test_log_memory_error(): void
    {
        $memoryUsage = 1024 * 1024 * 100; // 100MB
        
        $this->logger->logMemoryError('/test/large.php', 'Memory limit exceeded', $memoryUsage);

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        
        $error = $errors[0];
        $this->assertEquals('/test/large.php', $error->filePath);
        $this->assertEquals(ErrorCategory::MEMORY_ERROR, $error->category);
        $this->assertEquals(ErrorSeverity::CRITICAL, $error->severity);
        $this->assertEquals('Memory limit exceeded', $error->message);
        $this->assertEquals((float)$memoryUsage, $error->memoryUsage);
    }

    public function test_log_timeout_error(): void
    {
        $this->logger->logTimeoutError('/test/slow.php', 'Processing timeout', 30.5);

        $errors = $this->logger->getErrors();
        $this->assertCount(1, $errors);
        
        $error = $errors[0];
        $this->assertEquals('/test/slow.php', $error->filePath);
        $this->assertEquals(ErrorCategory::TIMEOUT_ERROR, $error->category);
        $this->assertEquals(ErrorSeverity::ERROR, $error->severity);
        $this->assertEquals('Processing timeout', $error->message);
        $this->assertEquals(30.5, $error->executionTime);
    }

    public function test_get_errors_by_category(): void
    {
        $this->logger->logSyntaxError('/test/syntax.php', 'Syntax error');
        $this->logger->logIoError('/test/io.php', 'IO error');
        $this->logger->logSyntaxError('/test/syntax2.php', 'Another syntax error');

        $syntaxErrors = $this->logger->getErrorsByCategory(ErrorCategory::SYNTAX_ERROR);
        $ioErrors = $this->logger->getErrorsByCategory(ErrorCategory::IO_ERROR);

        $this->assertCount(2, $syntaxErrors);
        $this->assertCount(1, $ioErrors);
    }

    public function test_get_errors_by_severity(): void
    {
        $this->logger->logSyntaxError('/test/warning.php', 'Warning');  // WARNING
        $this->logger->logIoError('/test/error.php', 'Error');         // ERROR
        $this->logger->logMemoryError('/test/critical.php', 'Critical', 1024); // CRITICAL

        $warnings = $this->logger->getErrorsBySeverity(ErrorSeverity::WARNING);
        $errors = $this->logger->getErrorsBySeverity(ErrorSeverity::ERROR);
        $critical = $this->logger->getErrorsBySeverity(ErrorSeverity::CRITICAL);

        $this->assertCount(1, $warnings);
        $this->assertCount(1, $errors);
        $this->assertCount(1, $critical);
    }

    public function test_get_error_count(): void
    {
        $this->logger->logSyntaxError('/test/1.php', 'Error 1');
        $this->logger->logSyntaxError('/test/2.php', 'Error 2');
        $this->logger->logIoError('/test/3.php', 'Error 3');

        $this->assertEquals(3, $this->logger->getErrorCount());
        $this->assertEquals(2, $this->logger->getErrorCount(ErrorCategory::SYNTAX_ERROR));
        $this->assertEquals(1, $this->logger->getErrorCount(ErrorCategory::IO_ERROR));
        $this->assertEquals(0, $this->logger->getErrorCount(ErrorCategory::MEMORY_ERROR));
    }

    public function test_error_summary(): void
    {
        $this->logger->logSyntaxError('/test/1.php', 'Error 1');
        $this->logger->logSyntaxError('/test/1.php', 'Error 2'); // Same file
        $this->logger->logSyntaxError('/test/1.php', 'Error 3'); // Same file (3+ errors = critical)
        $this->logger->logIoError('/test/2.php', 'Error 4');

        $summary = $this->logger->getErrorSummary();

        $this->assertEquals(4, $summary['total']);
        $this->assertIsArray($summary['byCategory']);
        $this->assertEquals(3, $summary['byCategory']['syntax']);
        $this->assertEquals(1, $summary['byCategory']['io']);
        $this->assertIsArray($summary['bySeverity']);
        $this->assertEquals(3, $summary['bySeverity']['warning']);
        $this->assertEquals(1, $summary['bySeverity']['error']);
        
        // Check critical files (files with 3+ errors)
        $this->assertIsArray($summary['criticalFiles']);
        $this->assertArrayHasKey('/test/1.php', $summary['criticalFiles']);
        $this->assertEquals(3, $summary['criticalFiles']['/test/1.php']);
    }

    public function test_has_exceeded_error_threshold(): void
    {
        // Add errors for 3 different files out of 10 total files
        $this->logger->logSyntaxError('/test/1.php', 'Error 1');
        $this->logger->logSyntaxError('/test/2.php', 'Error 2');
        $this->logger->logSyntaxError('/test/3.php', 'Error 3');

        // 3/10 = 30% error rate, threshold is 20%
        $this->assertTrue($this->logger->hasExceededErrorThreshold(10, 0.2));
        
        // 3/10 = 30% error rate, threshold is 40%
        $this->assertFalse($this->logger->hasExceededErrorThreshold(10, 0.4));
    }

    public function test_has_critical_errors(): void
    {
        $this->assertFalse($this->logger->hasCriticalErrors());

        $this->logger->logSyntaxError('/test/1.php', 'Warning');
        $this->assertFalse($this->logger->hasCriticalErrors());

        $this->logger->logMemoryError('/test/2.php', 'Critical', 1024);
        $this->assertTrue($this->logger->hasCriticalErrors());
    }

    public function test_max_errors_limit(): void
    {
        $logger = new ErrorLogger(maxErrors: 2);

        $logger->logSyntaxError('/test/1.php', 'Error 1');
        $logger->logSyntaxError('/test/2.php', 'Error 2');
        $logger->logSyntaxError('/test/3.php', 'Error 3'); // Should remove first error

        $errors = $logger->getErrors();
        $this->assertCount(2, $errors);
        $this->assertEquals('Error 2', $errors[0]->message);
        $this->assertEquals('Error 3', $errors[1]->message);
    }

    public function test_minimum_severity_filter(): void
    {
        $logger = new ErrorLogger(minSeverity: ErrorSeverity::ERROR);

        $logger->logSyntaxError('/test/1.php', 'Warning'); // WARNING - should be filtered
        $logger->logIoError('/test/2.php', 'Error');       // ERROR - should be logged

        $errors = $logger->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Error', $errors[0]->message);
    }

    public function test_clear_errors(): void
    {
        $this->logger->logSyntaxError('/test/1.php', 'Error 1');
        $this->logger->logIoError('/test/2.php', 'Error 2');

        $this->assertCount(2, $this->logger->getErrors());

        $this->logger->clear();

        $this->assertCount(0, $this->logger->getErrors());
        $this->assertEquals(0, $this->logger->getErrorCount());
    }

    public function test_to_array(): void
    {
        $this->logger->logSyntaxError('/test/file.php', 'Syntax error', 10, 'bad code');

        $array = $this->logger->toArray();

        $this->assertCount(1, $array);
        $this->assertEquals('/test/file.php', $array[0]['filePath']);
        $this->assertEquals('syntax', $array[0]['category']);
        $this->assertEquals('warning', $array[0]['severity']);
        $this->assertEquals('Syntax error', $array[0]['message']);
        $this->assertEquals(10, $array[0]['lineNumber']);
    }
}