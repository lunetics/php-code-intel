<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit\Finder;

use CodeIntel\Finder\UsageFinder;
use CodeIntel\Index\SymbolIndex;
use PHPUnit\Framework\TestCase;

class FindClassUsageTest extends TestCase
{
    private UsageFinder $finder;
    private SymbolIndex $index;

    protected function setUp(): void
    {
        $this->index = new SymbolIndex();
        $this->finder = new UsageFinder($this->index);
    }

    /**
     * @test
     */
    public function finds_direct_class_instantiation(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $this->assertNotEmpty($usages, 'Should find at least one usage');
        
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'new SimpleClass()')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                $this->assertEquals($testFile, $usage['file']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find "new SimpleClass()" usage');
    }

    /**
     * @test
     */
    public function finds_static_class_reference(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'SimpleClass::class')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find "SimpleClass::class" usage');
    }

    /**
     * @test
     */
    public function finds_instanceof_usage(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'instanceof SimpleClass')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find "instanceof SimpleClass" usage');
    }

    /**
     * @test
     */
    public function finds_type_hint_usage(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'function useClass(SimpleClass $instance)')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find type hint usage');
    }

    /**
     * @test
     */
    public function returns_empty_array_when_no_usages_found(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('NonExistentClass');
        
        // Assert
        $this->assertEmpty($usages);
    }

    /**
     * @test
     */
    public function includes_context_lines_in_usage(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Classes.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $this->assertNotEmpty($usages);
        $firstUsage = $usages[0];
        
        $this->assertArrayHasKey('context', $firstUsage);
        $this->assertArrayHasKey('start', $firstUsage['context']);
        $this->assertArrayHasKey('end', $firstUsage['context']);
        $this->assertArrayHasKey('lines', $firstUsage['context']);
        $this->assertIsArray($firstUsage['context']['lines']);
    }
}