<?php

declare(strict_types=1);

namespace TestFixtures\EdgeCases;

use Closure;

// Simple closures
$simpleClosure = function(): string {
    return 'simple';
};

$withParams = function(int $a, int $b): int {
    return $a + $b;
};

// Closures with use statements
$outerVar = 'outer';
$number = 10;

$withUse = function() use ($outerVar): string {
    return "Captured: $outerVar";
};

$withMultipleUse = function(string $prefix) use ($outerVar, $number): string {
    return "$prefix: $outerVar - $number";
};

// Closure with reference capture
$counter = 0;
$incrementer = function() use (&$counter): int {
    return ++$counter;
};

// Multiple closures sharing variables
$shared = 100;
$reader = function() use ($shared): int {
    return $shared;
};

$modifier = function(int $delta) use (&$shared): void {
    $shared += $delta;
};

// Closures binding $this
class ClosureContainer
{
    private string $privateProperty = 'private';
    protected string $protectedProperty = 'protected';
    public string $publicProperty = 'public';
    
    public function createClosure(): Closure
    {
        return function(): array {
            return [
                'private' => $this->privateProperty,
                'protected' => $this->protectedProperty,
                'public' => $this->publicProperty,
            ];
        };
    }
    
    public function createArrowFunction(): Closure
    {
        // Arrow functions automatically capture $this
        return fn(): string => $this->privateProperty . ' via arrow';
    }
    
    public function createStaticClosure(): Closure
    {
        // Static closure cannot use $this
        return static function(string $param): string {
            return "Static: $param";
        };
    }
}

// Static closures
$staticClosure = static function(): string {
    // Cannot use $this here
    return 'I am static';
};

// Closures as class properties
class ClosureProperties
{
    public Closure $publicClosure;
    private Closure $privateClosure;
    protected Closure $protectedClosure;
    
    public function __construct()
    {
        $this->publicClosure = function(): string {
            return 'public closure';
        };
        
        $this->privateClosure = function(): string {
            return 'private closure';
        };
        
        $this->protectedClosure = function(): string {
            return 'protected closure';
        };
    }
    
    public function executePrivate(): string
    {
        return ($this->privateClosure)();
    }
}

// Recursive closures
$factorial = function(int $n) use (&$factorial): int {
    if ($n <= 1) {
        return 1;
    }
    return $n * $factorial($n - 1);
};

$fibonacci = function(int $n) use (&$fibonacci): int {
    if ($n <= 1) {
        return $n;
    }
    return $fibonacci($n - 1) + $fibonacci($n - 2);
};

// Closure returning closure (currying)
$multiplier = function(int $factor): Closure {
    return function(int $number) use ($factor): int {
        return $number * $factor;
    };
};

$double = $multiplier(2);
$triple = $multiplier(3);

// Complex nested closures
$outer = function(string $outerParam): Closure {
    $outerLocal = 'outer local';
    
    return function(string $middleParam) use ($outerParam, $outerLocal): Closure {
        $middleLocal = 'middle local';
        
        return function(string $innerParam) use ($outerParam, $outerLocal, $middleParam, $middleLocal): string {
            return implode(' | ', [$outerParam, $outerLocal, $middleParam, $middleLocal, $innerParam]);
        };
    };
};

// Closure with variadic parameters
$variadic = function(string $first, ...$rest): array {
    return array_merge([$first], $rest);
};

// Immediately invoked closure (IIFE)
$result = (function(int $x, int $y): int {
    return $x * $y;
})(5, 10);

// Closure binding to different object
class A
{
    private string $property = 'A property';
}

class B
{
    private string $property = 'B property';
}

$getClosure = function(): string {
    return $this->property;
};

$a = new A();
$b = new B();

$boundToA = Closure::bind($getClosure, $a, A::class);
$boundToB = Closure::bind($getClosure, $b, B::class);

// Closure with type declarations
$typed = function(?string $nullable, int|float $union, mixed $mixed): ?array {
    if ($nullable === null) {
        return null;
    }
    return [$nullable, $union, $mixed];
};

// Arrow functions (PHP 7.4+)
$arrow = fn($x) => $x * 2;
$arrowWithType = fn(int $x): int => $x * 2;
$arrowMultiParam = fn($x, $y) => $x + $y;

// Arrow function capturing variables
$multiplierArrow = 10;
$arrowCapture = fn($x) => $x * $multiplierArrow;

// Closure as event handler
class EventEmitter
{
    private array $handlers = [];
    
    public function on(string $event, Closure $handler): void
    {
        $this->handlers[$event][] = $handler;
    }
    
    public function emit(string $event, mixed ...$args): void
    {
        if (isset($this->handlers[$event])) {
            foreach ($this->handlers[$event] as $handler) {
                $handler(...$args);
            }
        }
    }
}

$emitter = new EventEmitter();
$emitter->on('data', function($data) {
    echo "Received: $data\n";
});

// Closure with attributes (PHP 8.0+)
#[\Attribute]
class Handler {}

$attributedClosure = #[Handler] function(): void {
    echo "Handler closure\n";
};

// First-class callable syntax (PHP 8.1+)
class CallableClass
{
    public function method(int $x): int
    {
        return $x * 2;
    }
    
    public static function staticMethod(int $x): int
    {
        return $x * 3;
    }
}

$obj = new CallableClass();
$methodClosure = $obj->method(...);
$staticClosure = CallableClass::staticMethod(...);

// Closure with match expression (PHP 8.0+)
$matcher = function(string $type): string {
    return match($type) {
        'admin' => 'Full access',
        'user' => 'Limited access',
        'guest' => 'Read only',
        default => 'No access'
    };
};

// Complex state management with closures
function createCounter(int $initial = 0): array
{
    $count = $initial;
    
    return [
        'increment' => function(int $by = 1) use (&$count): int {
            return $count += $by;
        },
        'decrement' => function(int $by = 1) use (&$count): int {
            return $count -= $by;
        },
        'get' => function() use (&$count): int {
            return $count;
        },
        'reset' => function() use (&$count, $initial): void {
            $count = $initial;
        }
    ];
}

// Memoization with closures
$memoize = function(Closure $fn): Closure {
    $cache = [];
    
    return function(...$args) use ($fn, &$cache) {
        $key = serialize($args);
        if (!isset($cache[$key])) {
            $cache[$key] = $fn(...$args);
        }
        return $cache[$key];
    };
};

$expensiveOperation = $memoize(function(int $n): int {
    sleep(1); // Simulate expensive operation
    return $n * $n;
});

// Mutual recursion with closures
$isEven = null;
$isOdd = null;

$isEven = function(int $n) use (&$isOdd): bool {
    if ($n === 0) return true;
    if ($n === 1) return false;
    return $isOdd(abs($n) - 1);
};

$isOdd = function(int $n) use (&$isEven): bool {
    if ($n === 0) return false;
    if ($n === 1) return true;
    return $isEven(abs($n) - 1);
};

// Closure with generator
$generatorClosure = function(int $start, int $end): \Generator {
    for ($i = $start; $i <= $end; $i++) {
        yield $i;
    }
};

// Nested scope with modification
function createModifier(): array
{
    $outerScope = 'initial';
    
    $modifier = function(string $newValue) use (&$outerScope): void {
        $outerScope = $newValue;
    };
    
    $getter = function() use (&$outerScope): string {
        return $outerScope;
    };
    
    return compact('modifier', 'getter');
}