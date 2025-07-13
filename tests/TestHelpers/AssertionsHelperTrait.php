<?php

declare(strict_types=1);

namespace CodeIntel\Tests\TestHelpers;

trait AssertionsHelperTrait
{
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
    
    protected function createTestIndex(array $files): \CodeIntel\Index\SymbolIndex
    {
        $index = new \CodeIntel\Index\SymbolIndex();
        
        foreach ($files as $file) {
            $index->indexFile($file);
        }
        
        return $index;
    }
}