<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Integration;

use CodeIntel\Finder\UsageFinder;
use CodeIntel\Index\SymbolIndex;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the complete PHP Code Intelligence Tool workflow
 */
class EndToEndTest extends TestCase
{
    private UsageFinder $finder;
    private SymbolIndex $index;

    protected function setUp(): void
    {
        $this->index = new SymbolIndex();
        $this->finder = new UsageFinder($this->index);
        
        // Index all test fixtures for comprehensive testing
        $fixtures = [
            __DIR__ . '/../fixtures/BasicSymbols/Classes.php',
            __DIR__ . '/../fixtures/BasicSymbols/Methods.php',
            __DIR__ . '/../fixtures/Inheritance/ClassHierarchy.php',
            __DIR__ . '/../fixtures/DynamicFeatures/DynamicCalls.php',
        ];
        
        foreach ($fixtures as $fixture) {
            $this->index->indexFile($fixture);
        }
    }

    /**
     * @test
     */
    public function complete_class_usage_analysis(): void
    {
        // Test a comprehensive class analysis
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        $this->assertNotEmpty($usages, 'Should find SimpleClass usages');
        
        // Verify we found different types of usages
        $types = array_column($usages, 'type');
        $this->assertContains('instantiation', $types, 'Should find instantiation usage');
        
        // Verify confidence levels are properly assigned
        $confidences = array_unique(array_column($usages, 'confidence'));
        $this->assertContains('CERTAIN', $confidences, 'Should have certain confidence usages');
        
        // Verify context is included
        foreach ($usages as $usage) {
            $this->assertArrayHasKey('context', $usage);
            $this->assertArrayHasKey('lines', $usage['context']);
            $this->assertIsArray($usage['context']['lines']);
        }
    }

    /**
     * @test
     */
    public function complete_method_usage_analysis(): void
    {
        // Test method usage with all advanced patterns
        $usages = $this->finder->find('TestFixtures\BasicSymbols\MethodExamples::publicMethod');
        
        $this->assertNotEmpty($usages, 'Should find method usages');
        
        // Verify different method call patterns are detected
        $codeSnippets = array_column($usages, 'code');
        $codeString = implode(' ', $codeSnippets);
        
        $this->assertStringContainsString('publicMethod()', $codeString, 'Should find regular method calls');
        
        // Verify confidence scoring works for different patterns
        $confidenceCounts = array_count_values(array_column($usages, 'confidence'));
        $this->assertArrayHasKey('CERTAIN', $confidenceCounts, 'Should have certain confidence usages');
    }

    /**
     * @test  
     */
    public function handles_inheritance_patterns(): void
    {
        // Test inheritance-based usage detection
        $usages = $this->finder->find('TestFixtures\Inheritance\Animal::makeSound');
        
        $this->assertNotEmpty($usages, 'Should find inherited method usages');
        
        // Should find parent:: calls
        $codeSnippets = array_column($usages, 'code');
        $foundParentCall = false;
        
        foreach ($codeSnippets as $code) {
            if (str_contains($code, 'parent::makeSound()')) {
                $foundParentCall = true;
                break;
            }
        }
        
        $this->assertTrue($foundParentCall, 'Should find parent method calls');
    }

    /**
     * @test
     */
    public function handles_dynamic_patterns(): void
    {
        // Test dynamic usage detection
        $usages = $this->finder->find('TestFixtures\DynamicFeatures\Calculator::add');
        
        $this->assertNotEmpty($usages, 'Should find dynamic method usages');
        
        // Should include dynamic or possible confidence levels for variable method calls
        $confidences = array_column($usages, 'confidence');
        $hasDynamicOrPossible = in_array('DYNAMIC', $confidences) || in_array('POSSIBLE', $confidences);
        $this->assertTrue($hasDynamicOrPossible, 'Should have dynamic or possible confidence for variable method calls');
    }

    /**
     * @test
     */
    public function performance_within_acceptable_limits(): void
    {
        $start = microtime(true);
        
        // Perform multiple searches to test performance
        $searches = [
            'TestFixtures\BasicSymbols\SimpleClass',
            'TestFixtures\BasicSymbols\MethodExamples::publicMethod',
            'TestFixtures\Inheritance\Animal::makeSound',
        ];
        
        $totalUsages = 0;
        foreach ($searches as $symbol) {
            $usages = $this->finder->find($symbol);
            $totalUsages += count($usages);
        }
        
        $duration = microtime(true) - $start;
        
        // Performance assertions
        $this->assertLessThan(5.0, $duration, 'Should complete multiple searches within 5 seconds');
        $this->assertGreaterThan(10, $totalUsages, 'Should find meaningful number of usages');
        
        // Memory usage check
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->assertLessThan(100, $memoryUsage, 'Memory usage should be reasonable');
    }

    /**
     * @test
     */
    public function returns_structured_data_for_claude(): void
    {
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Verify output structure is suitable for Claude Code
        foreach ($usages as $usage) {
            $this->assertIsArray($usage);
            $this->assertArrayHasKey('file', $usage);
            $this->assertArrayHasKey('line', $usage);
            $this->assertArrayHasKey('code', $usage);
            $this->assertArrayHasKey('confidence', $usage);
            $this->assertArrayHasKey('type', $usage);
            $this->assertArrayHasKey('context', $usage);
            
            // Verify data types
            $this->assertIsString($usage['file']);
            $this->assertIsInt($usage['line']);
            $this->assertIsString($usage['code']);
            $this->assertIsString($usage['confidence']);
            $this->assertIsString($usage['type']);
            $this->assertIsArray($usage['context']);
            
            // Verify confidence levels are valid
            $this->assertContains($usage['confidence'], ['CERTAIN', 'PROBABLE', 'POSSIBLE', 'DYNAMIC']);
        }
    }

    /**
     * @test
     */
    public function handles_edge_cases_gracefully(): void
    {
        // Test with non-existent symbol
        $usages = $this->finder->find('NonExistent\Class\Name');
        $this->assertEmpty($usages);
        
        // Test with malformed symbol names
        $usages = $this->finder->find('');
        $this->assertEmpty($usages);
        
        $usages = $this->finder->find('\\\\\\InvalidName');
        $this->assertEmpty($usages);
    }
}