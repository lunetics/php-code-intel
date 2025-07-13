# TDD Test Plan - PHP Code Intelligence Tool

## Overview

This document outlines the Test-Driven Development (TDD) approach for the PHP Code Intelligence Tool. All tests must be written BEFORE implementation to ensure comprehensive coverage and correct behavior.

## TDD Philosophy

1. **Red**: Write a failing test
2. **Green**: Write minimal code to pass
3. **Refactor**: Improve code while keeping tests green

## Test Fixture Structure

### Directory Layout

```
tests/fixtures/
├── BasicSymbols/
│   ├── Classes.php
│   ├── Interfaces.php
│   ├── Traits.php
│   ├── Enums.php
│   ├── Functions.php
│   ├── Constants.php
│   ├── Properties.php
│   └── Methods.php
├── Inheritance/
│   ├── ClassHierarchy.php
│   ├── InterfaceImplementation.php
│   ├── TraitUsage.php
│   ├── AbstractClasses.php
│   └── MethodOverriding.php
├── Namespaces/
│   ├── NamespaceDeclarations.php
│   ├── UseStatements.php
│   ├── GroupedImports.php
│   └── Aliases.php
├── DynamicFeatures/
│   ├── MagicMethods.php
│   ├── DynamicProperties.php
│   ├── VariableFunctions.php
│   ├── CallUserFunc.php
│   └── Reflection.php
├── ModernPHP/
│   ├── TypedProperties.php
│   ├── UnionTypes.php
│   ├── Attributes.php
│   ├── Enums.php
│   ├── ReadonlyProperties.php
│   └── ConstructorPromotion.php
├── EdgeCases/
│   ├── AnonymousClasses.php
│   ├── Closures.php
│   ├── ArrowFunctions.php
│   ├── Generators.php
│   ├── VariadicFunctions.php
│   └── NullsafeOperator.php
└── RealWorld/
    ├── Laravel/
    │   ├── EloquentModels.php
    │   ├── Controllers.php
    │   └── Facades.php
    └── Symfony/
        ├── Controllers.php
        ├── Services.php
        └── Entities.php
```

## Test Fixtures Examples

### Basic Symbols

```php
// tests/fixtures/BasicSymbols/Classes.php
<?php
namespace TestFixtures\BasicSymbols;

// Simple class
class SimpleClass
{
    public function method() {}
}

// Final class
final class FinalClass {}

// Abstract class
abstract class AbstractClass
{
    abstract public function abstractMethod();
}

// Class with constructor
class ClassWithConstructor
{
    public function __construct(private string $name) {}
}

// Usage examples for testing
$instance = new SimpleClass();
$instance->method();
SimpleClass::class;
```

```php
// tests/fixtures/BasicSymbols/Methods.php
<?php
namespace TestFixtures\BasicSymbols;

class MethodExamples
{
    // Instance methods
    public function publicMethod() {}
    protected function protectedMethod() {}
    private function privateMethod() {}
    
    // Static methods
    public static function staticMethod() {}
    
    // Return types
    public function withReturnType(): string { return ''; }
    public function withNullableReturn(): ?string { return null; }
    public function withUnionReturn(): string|int { return ''; }
    
    // Parameters
    public function withParameters(string $required, ?int $optional = null) {}
    public function withVariadic(string ...$args) {}
    public function withMixed(mixed $param) {}
}

// Usage examples
$obj = new MethodExamples();
$obj->publicMethod();
MethodExamples::staticMethod();
$obj?->publicMethod(); // Nullsafe
```

### Dynamic Features

```php
// tests/fixtures/DynamicFeatures/MagicMethods.php
<?php
namespace TestFixtures\DynamicFeatures;

class MagicClass
{
    private array $data = [];
    
    public function __call($name, $arguments) {
        return "Called: $name";
    }
    
    public function __get($name) {
        return $this->data[$name] ?? null;
    }
    
    public function __set($name, $value) {
        $this->data[$name] = $value;
    }
    
    public static function __callStatic($name, $arguments) {
        return "Static call: $name";
    }
}

// Dynamic usage examples
$magic = new MagicClass();
$magic->undefinedMethod(); // Goes to __call
$magic->property = 'value'; // Goes to __set
echo $magic->property; // Goes to __get
MagicClass::staticUndefined(); // Goes to __callStatic

// Variable method calls
$method = 'process';
$magic->$method();
$magic->{'get' . 'Data'}();
```

### Edge Cases

```php
// tests/fixtures/EdgeCases/AnonymousClasses.php
<?php
namespace TestFixtures\EdgeCases;

interface Logger {
    public function log(string $message): void;
}

// Anonymous class implementing interface
$logger = new class implements Logger {
    public function log(string $message): void {
        echo $message;
    }
};

// Anonymous class extending base
abstract class BaseProcessor {
    abstract public function process(): void;
}

$processor = new class extends BaseProcessor {
    public function process(): void {
        // Implementation
    }
};

// Anonymous class with constructor
$service = new class('config') {
    public function __construct(private string $config) {}
    public function getConfig(): string {
        return $this->config;
    }
};
```

## Test Scenarios

### 1. Class Usage Tests

```php
class FindClassUsageTest extends TestCase
{
    /**
     * @test
     */
    public function finds_direct_instantiation(): void
    {
        // Arrange
        $finder = $this->createFinder();
        
        // Act
        $usages = $finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        // Assert
        $this->assertUsageFound($usages, [
            'file' => 'Classes.php',
            'line' => 28,
            'code' => 'new SimpleClass()',
            'confidence' => 'CERTAIN'
        ]);
    }
    
    /**
     * @test
     */
    public function finds_static_class_constant(): void
    {
        $usages = $finder->find('TestFixtures\BasicSymbols\SimpleClass');
        
        $this->assertUsageFound($usages, [
            'code' => 'SimpleClass::class',
            'confidence' => 'CERTAIN'
        ]);
    }
    
    /**
     * @test
     */
    public function finds_instanceof_usage(): void
    {
        // Test: if ($obj instanceof SimpleClass)
    }
    
    /**
     * @test
     */
    public function finds_catch_block_usage(): void
    {
        // Test: catch (SimpleClass $e)
    }
    
    /**
     * @test
     */
    public function finds_type_hint_usage(): void
    {
        // Test: function process(SimpleClass $obj)
    }
}
```

### 2. Method Usage Tests

```php
class FindMethodUsageTest extends TestCase
{
    /**
     * @test
     */
    public function finds_instance_method_call(): void
    {
        $usages = $finder->find('MethodExamples::publicMethod');
        
        $this->assertUsageFound($usages, [
            'code' => '$obj->publicMethod()',
            'confidence' => 'CERTAIN'
        ]);
    }
    
    /**
     * @test
     */
    public function finds_static_method_call(): void
    {
        $usages = $finder->find('MethodExamples::staticMethod');
        
        $this->assertUsageFound($usages, [
            'code' => 'MethodExamples::staticMethod()',
            'confidence' => 'CERTAIN'
        ]);
    }
    
    /**
     * @test
     */
    public function finds_parent_method_call(): void
    {
        // Test: parent::method()
    }
    
    /**
     * @test
     */
    public function finds_dynamic_method_call_with_lower_confidence(): void
    {
        $usages = $finder->find('MagicClass::undefinedMethod');
        
        $this->assertUsageFound($usages, [
            'code' => '$magic->undefinedMethod()',
            'confidence' => 'DYNAMIC'
        ]);
    }
}
```

### 3. Confidence Level Tests

```php
class ConfidenceScoringTest extends TestCase
{
    /**
     * @test
     * @dataProvider confidenceProvider
     */
    public function assigns_correct_confidence_level(
        string $code,
        string $expectedConfidence
    ): void {
        $scorer = new ConfidenceScorer();
        $confidence = $scorer->score($code);
        
        $this->assertEquals($expectedConfidence, $confidence);
    }
    
    public function confidenceProvider(): array
    {
        return [
            // CERTAIN - Direct, unambiguous usage
            ['new ClassName()', 'CERTAIN'],
            ['ClassName::method()', 'CERTAIN'],
            ['ClassName::CONSTANT', 'CERTAIN'],
            
            // PROBABLE - Type-hinted, documented
            ['/** @var ClassName $var */ $var->method()', 'PROBABLE'],
            ['function(ClassName $param) { $param->method(); }', 'PROBABLE'],
            
            // POSSIBLE - Dynamic but traceable
            ['$className = "ClassName"; new $className()', 'POSSIBLE'],
            ['$obj->$methodName()', 'POSSIBLE'],
            
            // DYNAMIC - Magic methods, truly dynamic
            ['$obj->__call("method", [])', 'DYNAMIC'],
            ['call_user_func([$obj, "method"])', 'DYNAMIC'],
        ];
    }
}
```

### 4. Performance Tests

```php
class PerformanceTest extends TestCase
{
    /**
     * @test
     */
    public function indexes_1000_files_within_time_limit(): void
    {
        // Arrange
        $files = $this->generate1000TestFiles();
        $indexer = new SymbolIndexer();
        
        // Act
        $start = microtime(true);
        $indexer->indexFiles($files);
        $duration = microtime(true) - $start;
        
        // Assert
        $this->assertLessThan(10.0, $duration, 'Indexing took too long');
    }
    
    /**
     * @test
     */
    public function finds_usage_with_cache_under_100ms(): void
    {
        // Arrange - ensure cache is warm
        $finder = $this->createFinderWithCache();
        
        // Act
        $start = microtime(true);
        $finder->find('ClassName::method');
        $duration = (microtime(true) - $start) * 1000;
        
        // Assert
        $this->assertLessThan(100, $duration, 'Finding took too long');
    }
}
```

## Integration Tests

### End-to-End Workflow

```php
class EndToEndTest extends TestCase
{
    /**
     * @test
     */
    public function complete_workflow_from_index_to_find(): void
    {
        // 1. Index a codebase
        $this->artisan('code-intel:index', ['path' => './tests/fixtures']);
        
        // 2. Find class usage
        $output = $this->artisan('code-intel:find', [
            'symbol' => 'SimpleClass'
        ]);
        
        // 3. Verify JSON output
        $json = json_decode($output, true);
        $this->assertArrayHasKey('usages', $json);
        $this->assertNotEmpty($json['usages']);
    }
}
```

### Real Project Tests

```php
class RealProjectTest extends TestCase
{
    /**
     * @test
     * @group slow
     */
    public function handles_laravel_project(): void
    {
        // Test against a real Laravel project
        $finder = $this->createFinder();
        $finder->index('./vendor/laravel/framework/src');
        
        // Find Eloquent Model usage
        $usages = $finder->find('Illuminate\Database\Eloquent\Model');
        
        $this->assertGreaterThan(100, count($usages));
    }
}
```

## Test Helpers

### Custom Assertions

```php
trait CustomAssertions
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
        
        $this->assertTrue($found, 'Expected usage not found: ' . json_encode($expected));
    }
    
    protected function assertConfidenceLevel(string $code, string $expected): void
    {
        $scorer = new ConfidenceScorer();
        $actual = $scorer->score($code);
        
        $this->assertEquals(
            $expected,
            $actual,
            "Expected confidence $expected but got $actual for: $code"
        );
    }
}
```

## Coverage Requirements

### Minimum Coverage Targets

- Overall: 90%
- Core Components:
  - SymbolIndexer: 95%
  - UsageFinder: 95%
  - ConfidenceScorer: 100%
  - Output Formatters: 90%

### Critical Paths

These must have 100% coverage:
1. Symbol resolution algorithm
2. Confidence scoring logic
3. Cache invalidation
4. Error handling

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=unit
./vendor/bin/phpunit --testsuite=integration

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run specific test file
./vendor/bin/phpunit tests/Unit/FindClassUsageTest.php

# Run tests in parallel (faster)
./vendor/bin/paratest -p 4
```

## Continuous Testing

```bash
# Watch mode - rerun on changes
./vendor/bin/phpunit-watcher watch

# Pre-commit hook
#!/bin/bash
./vendor/bin/phpunit --stop-on-failure
```

---

*This test plan ensures comprehensive coverage of all PHP symbols and usage patterns. Follow TDD strictly - no production code without a failing test first!*