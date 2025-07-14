<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit\ErrorHandling;

use CodeIntel\Error\ErrorCategory;
use CodeIntel\Error\ErrorSeverity;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ErrorCategory enum
 */
class ErrorCategoryTest extends TestCase
{
    public function test_all_categories_have_descriptions(): void
    {
        foreach (ErrorCategory::cases() as $category) {
            $description = $category->getDescription();
            $this->assertNotEmpty($description);
            // Most descriptions contain 'error', but some may contain related terms
            $hasErrorTerm = str_contains(strtolower($description), 'error') || 
                           str_contains(strtolower($description), 'timeout') ||
                           str_contains(strtolower($description), 'memory') ||
                           str_contains(strtolower($description), 'configuration');
            $this->assertTrue($hasErrorTerm, "Description '{$description}' should contain error-related term");
        }
    }

    public function test_all_categories_have_severity(): void
    {
        foreach (ErrorCategory::cases() as $category) {
            $severity = $category->getSeverity();
            $this->assertInstanceOf(ErrorSeverity::class, $severity);
        }
    }

    public function test_critical_categories_stop_processing(): void
    {
        $this->assertTrue(ErrorCategory::MEMORY_ERROR->shouldStopProcessing());
        $this->assertTrue(ErrorCategory::CONFIGURATION_ERROR->shouldStopProcessing());
    }

    public function test_non_critical_categories_continue_processing(): void
    {
        $this->assertFalse(ErrorCategory::SYNTAX_ERROR->shouldStopProcessing());
        $this->assertFalse(ErrorCategory::IO_ERROR->shouldStopProcessing());
        $this->assertFalse(ErrorCategory::PARSER_ERROR->shouldStopProcessing());
        $this->assertFalse(ErrorCategory::TIMEOUT_ERROR->shouldStopProcessing());
        $this->assertFalse(ErrorCategory::INDEX_ERROR->shouldStopProcessing());
    }

    public function test_syntax_error_has_warning_severity(): void
    {
        $this->assertEquals(ErrorSeverity::WARNING, ErrorCategory::SYNTAX_ERROR->getSeverity());
    }

    public function test_memory_error_has_critical_severity(): void
    {
        $this->assertEquals(ErrorSeverity::CRITICAL, ErrorCategory::MEMORY_ERROR->getSeverity());
    }

    /**
     * @dataProvider categoryDescriptionProvider
     */
    public function test_category_descriptions(ErrorCategory $category, string $expectedKeyword): void
    {
        $description = $category->getDescription();
        $this->assertStringContainsString($expectedKeyword, strtolower($description));
    }

    /** @return array<string, array{0: ErrorCategory, 1: string}> */
    public static function categoryDescriptionProvider(): array
    {
        return [
            'syntax error' => [ErrorCategory::SYNTAX_ERROR, 'syntax'],
            'io error' => [ErrorCategory::IO_ERROR, 'file'],
            'memory error' => [ErrorCategory::MEMORY_ERROR, 'memory'],
            'timeout error' => [ErrorCategory::TIMEOUT_ERROR, 'timeout'],
            'parser error' => [ErrorCategory::PARSER_ERROR, 'parser'],
            'configuration error' => [ErrorCategory::CONFIGURATION_ERROR, 'configuration'],
            'index error' => [ErrorCategory::INDEX_ERROR, 'index'],
        ];
    }

    public function test_error_category_values(): void
    {
        $this->assertEquals('syntax', ErrorCategory::SYNTAX_ERROR->value);
        $this->assertEquals('io', ErrorCategory::IO_ERROR->value);
        $this->assertEquals('memory', ErrorCategory::MEMORY_ERROR->value);
        $this->assertEquals('timeout', ErrorCategory::TIMEOUT_ERROR->value);
        $this->assertEquals('parser', ErrorCategory::PARSER_ERROR->value);
        $this->assertEquals('configuration', ErrorCategory::CONFIGURATION_ERROR->value);
        $this->assertEquals('index', ErrorCategory::INDEX_ERROR->value);
    }
}