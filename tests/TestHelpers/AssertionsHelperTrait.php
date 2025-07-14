<?php

declare(strict_types=1);

namespace CodeIntel\Tests\TestHelpers;

trait AssertionsHelperTrait
{
    /**
     * @param array<array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}}} $usages
     * @param array<string, mixed> $expected
     */
    protected function assertUsageFound(array $usages, array $expected): void
    {
        $found = false;
        foreach ($usages as $usage) {
            if ($this->usageMatches($usage, $expected)) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue(
            $found,
            'Expected usage not found: ' . json_encode($expected, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * @param array<array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}}} $usages
     * @param array<string, mixed> $expected
     */
    protected function assertUsageNotFound(array $usages, array $expected): void
    {
        $found = false;
        foreach ($usages as $usage) {
            if ($this->usageMatches($usage, $expected)) {
                $found = true;
                break;
            }
        }
        
        $this->assertFalse(
            $found,
            'Unexpected usage found: ' . json_encode($expected, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * @param array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}} $usage
     * @param array<string, mixed> $expected
     */
    private function usageMatches(array $usage, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            if (!isset($usage[$key])) {
                return false;
            }
            
            if ($key === 'code') {
                if (!str_contains($usage[$key], $value)) {
                    return false;
                }
            } elseif ($usage[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function assertConfidenceLevel(string $code, string $expected): void
    {
        $scorer = new \CodeIntel\Finder\ConfidenceScorer();
        $actual = $scorer->score($code);
        
        $this->assertEquals(
            $expected,
            $actual,
            "Expected confidence $expected but got $actual for: $code"
        );
    }
    
    /**
     * @param array<string> $files
     */
    protected function createTestIndex(array $files): \CodeIntel\Index\SymbolIndex
    {
        $index = new \CodeIntel\Index\SymbolIndex();
        
        foreach ($files as $file) {
            $index->indexFile($file);
        }
        
        return $index;
    }
}