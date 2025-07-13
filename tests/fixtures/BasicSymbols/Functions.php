<?php

/**
 * PHP Functions Test Fixture
 * 
 * This file contains examples of various PHP function types for testing
 * code intelligence and parsing capabilities.
 */

// =============================================================================
// 1. GLOBAL FUNCTIONS (OUTSIDE NAMESPACE)
// =============================================================================

/**
 * Simple global function without parameters
 */
function globalSimpleFunction(): string
{
    return "Hello from global function!";
}

/**
 * Global function with parameters (untyped for legacy compatibility)
 */
function globalFunctionWithParams($param1, $param2)
{
    return $param1 . ' ' . $param2;
}

/**
 * Global function with typed parameters and return type
 */
function globalFunctionWithTypes(string $name, int $age): string
{
    return "Name: $name, Age: $age";
}

// Usage examples
echo globalSimpleFunction() . PHP_EOL;
echo globalFunctionWithParams("Hello", "World") . PHP_EOL;
echo globalFunctionWithTypes("John", 30) . PHP_EOL;

// =============================================================================
// 2. NAMESPACED FUNCTIONS
// =============================================================================

namespace TestNamespace {
    
    /**
     * Simple namespaced function
     */
    function namespacedFunction(): string
    {
        return "Hello from TestNamespace!";
    }
    
    /**
     * Namespaced function with parameters
     */
    function processData(array $data, bool $validate = true): array
    {
        if ($validate) {
            // validation logic
        }
        return $data;
    }
}

namespace AnotherNamespace\SubNamespace {
    
    /**
     * Deeply nested namespaced function
     */
    function deeplyNestedFunction(mixed $input): void
    {
        echo "Processing in deep namespace: " . print_r($input, true);
    }
    
    /**
     * Function with mixed return type
     */
    function flexibleFunction($value): mixed
    {
        return is_numeric($value) ? (int)$value : $value;
    }
}

// Usage of namespaced functions
namespace {
    echo TestNamespace\namespacedFunction() . PHP_EOL;
    $result = TestNamespace\processData(['a', 'b', 'c']);
    AnotherNamespace\SubNamespace\deeplyNestedFunction("test");
}

// =============================================================================
// 3. FUNCTIONS WITH VARIOUS PARAMETER TYPES
// =============================================================================

namespace TypeExamples {
    
    /**
     * Function with scalar type parameters
     */
    function scalarTypes(
        string $str,
        int $int,
        float $float,
        bool $bool
    ): void {
        // Process scalar types
    }
    
    /**
     * Function with compound type parameters
     */
    function compoundTypes(
        array $array,
        object $object,
        callable $callback,
        iterable $items
    ): void {
        // Process compound types
    }
    
    /**
     * Function with nullable types
     */
    function nullableTypes(
        ?string $nullableString,
        ?array $nullableArray = null
    ): ?int {
        return $nullableString ? strlen($nullableString) : null;
    }
    
    /**
     * Function with union types (PHP 8.0+)
     */
    function unionTypes(string|int $id, array|object $data): string|false
    {
        if (is_string($id) && strlen($id) > 0) {
            return "Valid ID: $id";
        }
        return false;
    }
    
    /**
     * Function with intersection types (PHP 8.1+)
     */
    function intersectionTypes(\Stringable&\JsonSerializable $object): string
    {
        return (string)$object;
    }
    
    /**
     * Function with mixed type
     */
    function mixedType(mixed $anything): mixed
    {
        return $anything;
    }
}

// =============================================================================
// 4. FUNCTIONS WITH RETURN TYPES
// =============================================================================

namespace ReturnTypes {
    
    /**
     * Function returning string
     */
    function getString(): string
    {
        return "Hello";
    }
    
    /**
     * Function returning array with specific structure
     */
    function getArray(): array
    {
        return ['key' => 'value', 'count' => 42];
    }
    
    /**
     * Function with void return type
     */
    function doSomething(): void
    {
        // Performs action but returns nothing
        echo "Doing something..." . PHP_EOL;
    }
    
    /**
     * Function with never return type (PHP 8.1+)
     */
    function alwaysThrows(): never
    {
        throw new \RuntimeException("This function never returns");
    }
    
    /**
     * Function returning self (in class context)
     */
    class FluentClass {
        public function setSomething(): self
        {
            // ... do something
            return $this;
        }
        
        public function setAnother(): static
        {
            // ... do something else
            return $this;
        }
    }
}

// =============================================================================
// 5. VARIADIC FUNCTIONS
// =============================================================================

/**
 * Basic variadic function
 */
function variadicFunction(string ...$strings): string
{
    return implode(', ', $strings);
}

/**
 * Variadic function with type constraint
 */
function variadicWithTypes(int ...$numbers): int
{
    return array_sum($numbers);
}

/**
 * Function with regular and variadic parameters
 */
function mixedVariadic(string $prefix, string ...$items): string
{
    return $prefix . ': ' . implode(', ', $items);
}

// Usage examples
echo variadicFunction('a', 'b', 'c', 'd') . PHP_EOL;
echo variadicWithTypes(1, 2, 3, 4, 5) . PHP_EOL;
echo mixedVariadic('Items', 'apple', 'banana', 'orange') . PHP_EOL;

// =============================================================================
// 6. FUNCTIONS WITH DEFAULT PARAMETERS
// =============================================================================

/**
 * Function with simple default parameters
 */
function withDefaults(
    string $name = 'Guest',
    int $age = 0,
    bool $active = true
): string {
    return "User: $name, Age: $age, Active: " . ($active ? 'Yes' : 'No');
}

/**
 * Function with array default
 */
function withArrayDefault(array $options = []): array
{
    return array_merge(['debug' => false, 'cache' => true], $options);
}

/**
 * Function with null default and type declaration
 */
function withNullDefault(?string $value = null): string
{
    return $value ?? 'default value';
}

/**
 * Function with multiple complex defaults
 */
function complexDefaults(
    string $host = 'localhost',
    int $port = 3306,
    array $options = ['timeout' => 30],
    ?callable $logger = null
): void {
    // Connection logic here
}

// Usage examples
echo withDefaults() . PHP_EOL;
echo withDefaults('John') . PHP_EOL;
echo withDefaults('Jane', 25) . PHP_EOL;
echo withDefaults('Bob', 30, false) . PHP_EOL;

// =============================================================================
// 7. ARROW FUNCTIONS (PHP 7.4+)
// =============================================================================

// Simple arrow function
$simple = fn($x) => $x * 2;

// Arrow function with types
$typed = fn(int $x): int => $x * 2;

// Arrow function capturing variable from outer scope
$factor = 10;
$multiply = fn($x) => $x * $factor;

// Using arrow functions with array functions
$numbers = [1, 2, 3, 4, 5];
$doubled = array_map(fn($n) => $n * 2, $numbers);
$evens = array_filter($numbers, fn($n) => $n % 2 === 0);

// Arrow function with multiple parameters
$add = fn($a, $b) => $a + $b;

// Arrow function with complex expression
$categorize = fn($age) => match(true) {
    $age < 18 => 'minor',
    $age < 65 => 'adult',
    default => 'senior'
};

// Usage examples
echo $simple(5) . PHP_EOL;
echo $multiply(5) . PHP_EOL;
print_r($doubled);
echo $categorize(25) . PHP_EOL;

// =============================================================================
// 8. CLOSURES WITH USE STATEMENTS
// =============================================================================

// Simple closure
$simpleClosure = function($name) {
    return "Hello, $name!";
};

// Closure with single use
$prefix = "Mr.";
$withUse = function($name) use ($prefix) {
    return "$prefix $name";
};

// Closure with multiple use
$title = "Dr.";
$suffix = "PhD";
$multipleUse = function($name) use ($title, $suffix) {
    return "$title $name, $suffix";
};

// Closure with reference (mutable capture)
$counter = 0;
$increment = function() use (&$counter) {
    return ++$counter;
};

// Typed closure
$typedClosure = function(string $input): int {
    return strlen($input);
};

// Closure with typed parameters and use
$multiplier = 5;
$calculate = function(int $value): int use ($multiplier) {
    return $value * $multiplier;
};

// Static closure (cannot access $this)
$staticClosure = static function($x) {
    return $x * 2;
};

// Nested closures
$outer = function($x) {
    return function($y) use ($x) {
        return $x + $y;
    };
};

// Immediately invoked function expression (IIFE)
$result = (function($a, $b) {
    return $a + $b;
})(10, 20);

// Using closures as callbacks
$data = ['apple', 'banana', 'cherry'];
$lengths = array_map(function($item) {
    return strlen($item);
}, $data);

// Usage examples
echo $simpleClosure("World") . PHP_EOL;
echo $withUse("Smith") . PHP_EOL;
echo $multipleUse("Johnson") . PHP_EOL;
echo $increment() . PHP_EOL; // 1
echo $increment() . PHP_EOL; // 2
echo $calculate(10) . PHP_EOL; // 50

$addFive = $outer(5);
echo $addFive(3) . PHP_EOL; // 8

// =============================================================================
// ADDITIONAL ADVANCED EXAMPLES
// =============================================================================

namespace Advanced {
    
    /**
     * Higher-order function returning a closure
     */
    function createMultiplier(int $factor): \Closure
    {
        return function(int $value) use ($factor): int {
            return $value * $factor;
        };
    }
    
    /**
     * Function accepting callable parameter
     */
    function processArray(array $data, callable $processor): array
    {
        return array_map($processor, $data);
    }
    
    /**
     * Generator function
     */
    function generateNumbers(int $start, int $end): \Generator
    {
        for ($i = $start; $i <= $end; $i++) {
            yield $i;
        }
    }
    
    /**
     * Generator with key-value pairs
     */
    function generatePairs(): \Generator
    {
        yield 'first' => 1;
        yield 'second' => 2;
        yield 'third' => 3;
    }
    
    // Usage
    $double = createMultiplier(2);
    echo $double(5) . PHP_EOL; // 10
    
    $squared = processArray([1, 2, 3, 4], fn($x) => $x * $x);
    print_r($squared); // [1, 4, 9, 16]
    
    foreach (generateNumbers(1, 5) as $number) {
        echo $number . ' ';
    }
    echo PHP_EOL;
}