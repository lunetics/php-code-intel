<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit\Finder;

use CodeIntel\Finder\UsageFinder;
use CodeIntel\Index\SymbolIndex;
use PHPUnit\Framework\TestCase;

class FindMethodUsageTest extends TestCase
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
    public function finds_instance_method_call(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Methods.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\MethodExamples::publicMethod');
        
        // Assert
        $this->assertNotEmpty($usages, 'Should find method usage');
        
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], '$obj->publicMethod()')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find instance method call');
    }

    /**
     * @test
     */
    public function finds_static_method_call(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Methods.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\MethodExamples::staticMethod');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'MethodExamples::staticMethod()')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find static method call');
    }

    /**
     * @test
     */
    public function finds_nullsafe_method_call(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/BasicSymbols/Methods.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\BasicSymbols\MethodExamples::publicMethod');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], '$obj?->publicMethod()')) {
                $found = true;
                $this->assertEquals('PROBABLE', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find nullsafe method call');
    }

    /**
     * @test
     */
    public function finds_parent_method_call(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/Inheritance/ClassHierarchy.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\Inheritance\Animal::makeSound');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], 'parent::makeSound()')) {
                $found = true;
                $this->assertEquals('CERTAIN', $usage['confidence']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find parent method call');
    }

    /**
     * @test
     */
    public function finds_dynamic_method_call_with_lower_confidence(): void
    {
        // Arrange
        $testFile = __DIR__ . '/../../fixtures/DynamicFeatures/DynamicCalls.php';
        $this->index->indexFile($testFile);
        
        // Act
        $usages = $this->finder->find('TestFixtures\DynamicFeatures\Calculator::add');
        
        // Assert
        $found = false;
        foreach ($usages as $usage) {
            if (str_contains($usage['code'], '$calc->$method(')) {
                $found = true;
                $this->assertContains($usage['confidence'], ['POSSIBLE', 'DYNAMIC']);
                break;
            }
        }
        
        $this->assertTrue($found, 'Should find dynamic method call');
    }

    /**
     * @test
     */
    public function distinguishes_between_methods_with_same_name(): void
    {
        // Arrange
        $testFile1 = __DIR__ . '/../../fixtures/BasicSymbols/Methods.php';
        $testFile2 = __DIR__ . '/../../fixtures/Inheritance/ClassHierarchy.php';
        $this->index->indexFile($testFile1);
        $this->index->indexFile($testFile2);
        
        // Act
        $usages1 = $this->finder->find('TestFixtures\BasicSymbols\MethodExamples::publicMethod');
        $usages2 = $this->finder->find('TestFixtures\Inheritance\Animal::getName');
        
        // Assert
        $this->assertNotEmpty($usages1);
        $this->assertNotEmpty($usages2);
        
        // Ensure no cross-contamination
        foreach ($usages1 as $usage) {
            $this->assertStringNotContainsString('Animal', $usage['code']);
        }
        
        foreach ($usages2 as $usage) {
            $this->assertStringNotContainsString('MethodExamples', $usage['code']);
        }
    }
}