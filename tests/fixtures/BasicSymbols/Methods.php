<?php

declare(strict_types=1);

namespace TestFixtures\BasicSymbols;

/**
 * Demonstrates all PHP method types for testing code intelligence tools
 */
class MethodExamples
{
    public function publicMethod(): string
    {
        return 'Public method';
    }
    
    public static function staticMethod(): string
    {
        return 'Static method';
    }
}

/**
 * Demonstrates all PHP method types for testing code intelligence tools
 */
class Methods
{
    private string $name = 'Methods';
    
    // 1. VISIBILITY MODIFIERS
    
    public function publicMethod(): string
    {
        return 'Public method accessible from anywhere';
    }
    
    protected function protectedMethod(): string
    {
        return 'Protected method accessible in class and subclasses';
    }
    
    private function privateMethod(): string
    {
        return 'Private method accessible only in this class';
    }
    
    // 2. STATIC METHODS
    
    public static function staticMethod(): string
    {
        return 'Static method called without instantiation';
    }
    
    private static function privateStaticMethod(): int
    {
        return 42;
    }
    
    public static function staticWithSelfReference(): int
    {
        return self::privateStaticMethod() * 2;
    }
    
    // 3. FINAL METHODS
    
    final public function finalMethod(): string
    {
        return 'This method cannot be overridden';
    }
    
    // 4. METHODS WITH VARIOUS RETURN TYPES
    
    public function returnsVoid(): void
    {
        // Does something but returns nothing
        $this->privateMethod();
    }
    
    public function returnsString(): string
    {
        return 'A string value';
    }
    
    public function returnsNullableString(): ?string
    {
        return rand(0, 1) ? 'string' : null;
    }
    
    public function returnsUnionType(): string|int
    {
        return rand(0, 1) ? 'string' : 123;
    }
    
    public function returnsMixed(): mixed
    {
        return match(rand(0, 3)) {
            0 => 'string',
            1 => 123,
            2 => ['array'],
            default => null,
        };
    }
    
    public function returnsArray(): array
    {
        return ['key' => 'value', 'number' => 123];
    }
    
    public function returnsSelf(): self
    {
        return $this;
    }
    
    public function returnsStatic(): static
    {
        return new static();
    }
    
    public function returnsGenerator(): \Generator
    {
        yield 1;
        yield 2;
        yield 3;
    }
    
    // 5. METHODS WITH VARIOUS PARAMETER TYPES
    
    public function withScalarParams(string $str, int $int, float $float, bool $bool): void
    {
        // Process scalar parameters
    }
    
    public function withComplexParams(array $array, object $object, callable $callable): void
    {
        // Process complex parameters
    }
    
    public function withNullableParam(?string $nullable): ?string
    {
        return $nullable;
    }
    
    public function withUnionParam(string|int $mixed): string
    {
        return is_string($mixed) ? $mixed : (string)$mixed;
    }
    
    public function withMixedParam(mixed $anything): mixed
    {
        return $anything;
    }
    
    // 6. VARIADIC METHODS
    
    public function variadicMethod(...$args): array
    {
        return $args;
    }
    
    public function typedVariadic(string $first, int ...$numbers): int
    {
        return array_sum($numbers);
    }
    
    public function variadicWithTypes(string $prefix, string ...$strings): string
    {
        return $prefix . ': ' . implode(', ', $strings);
    }
    
    // 7. METHODS WITH DEFAULT PARAMETERS
    
    public function withDefaults(string $required, string $optional = 'default', ?int $nullable = null): string
    {
        return sprintf('%s-%s-%s', $required, $optional, $nullable ?? 'null');
    }
    
    public function multipleDefaults(
        string $name = 'John',
        int $age = 30,
        bool $active = true,
        ?array $tags = null
    ): array {
        return compact('name', 'age', 'active', 'tags');
    }
    
    public function mixedRequiredAndOptional(string $required, int $optional = 10): int
    {
        return strlen($required) + $optional;
    }
}

// Abstract class with abstract methods
abstract class AbstractMethods
{
    abstract public function abstractMethod(): void;
    
    abstract protected function abstractWithParams(string $param): string;
    
    abstract public function abstractWithReturn(): int;
    
    public function concreteMethod(): string
    {
        return 'Concrete method in abstract class';
    }
}

// Concrete implementation
class ConcreteMethods extends AbstractMethods
{
    public function abstractMethod(): void
    {
        // Implementation
    }
    
    protected function abstractWithParams(string $param): string
    {
        return "Processed: $param";
    }
    
    public function abstractWithReturn(): int
    {
        return 100;
    }
}

// Class demonstrating inheritance
class ChildMethods extends Methods
{
    public function publicMethod(): string
    {
        return 'Overridden: ' . parent::publicMethod();
    }
    
    protected function protectedMethod(): string
    {
        return 'Child can override protected method';
    }
    
    // Cannot override private methods - this is a new method
    private function privateMethod(): string
    {
        return 'Child\'s own private method';
    }
    
    // Cannot override final methods
    // public function finalMethod(): string {} // This would cause an error
}

// Interface with method signatures
interface MethodInterface
{
    public function interfaceMethod(): void;
    
    public function interfaceWithParams(string $param): string;
    
    public static function staticInterfaceMethod(): int;
}

// Trait with methods
trait MethodTrait
{
    public function traitMethod(): string
    {
        return 'Method from trait';
    }
    
    protected function traitProtected(): int
    {
        return 999;
    }
}

// Class using interface and trait
class CompleteExample implements MethodInterface
{
    use MethodTrait;
    
    public function interfaceMethod(): void
    {
        // Implementation
    }
    
    public function interfaceWithParams(string $param): string
    {
        return strtoupper($param);
    }
    
    public static function staticInterfaceMethod(): int
    {
        return 42;
    }
}

// USAGE EXAMPLES

// 1. Basic instantiation and method calls
$methods = new Methods();

// Public method calls
$result1 = $methods->publicMethod();
$result2 = $methods->returnsString();
$result3 = $methods->returnsNullableString();

// Static method calls
$static1 = Methods::staticMethod();
$static2 = Methods::staticWithSelfReference();

// Static method calls for testing
MethodExamples::staticMethod();

// 2. Methods with parameters
$methods->withScalarParams('hello', 123, 3.14, true);
$methods->withComplexParams(['a', 'b'], new \stdClass(), 'strlen');
$methods->withNullableParam(null);
$methods->withNullableParam('not null');

// Instance method calls for testing
$obj = new MethodExamples();
$obj->publicMethod();
$obj?->publicMethod();

// 3. Union type handling
$union1 = $methods->withUnionParam('string');
$union2 = $methods->withUnionParam(123);

// 4. Variadic methods
$variadic1 = $methods->variadicMethod(1, 2, 3, 4, 5);
$variadic2 = $methods->typedVariadic('sum', 10, 20, 30);
$variadic3 = $methods->variadicWithTypes('Colors', 'red', 'green', 'blue');

// 5. Default parameters
$default1 = $methods->withDefaults('required');
$default2 = $methods->withDefaults('required', 'custom');
$default3 = $methods->withDefaults('required', 'custom', 42);

$default4 = $methods->multipleDefaults();
$default5 = $methods->multipleDefaults('Jane');
$default6 = $methods->multipleDefaults('Jane', 25, false, ['tag1', 'tag2']);

// 6. Method chaining
$chain = $methods->returnsSelf()->returnsString();

// 7. Generator usage
foreach ($methods->returnsGenerator() as $value) {
    // Process yielded values
}

// 8. Abstract class usage
$concrete = new ConcreteMethods();
$concrete->abstractMethod();
$concrete->abstractWithParams('test');
$concrete->concreteMethod();

// 9. Inheritance
$child = new ChildMethods();
$child->publicMethod(); // Calls overridden method
$child->finalMethod(); // Calls parent's final method

// Parent method calls for testing
parent::makeSound();

// 10. Interface and trait usage
$complete = new CompleteExample();
$complete->interfaceMethod();
$complete->interfaceWithParams('hello');
$complete->traitMethod();
CompleteExample::staticInterfaceMethod();

// 11. Anonymous class with methods
$anonymous = new class {
    public function anonymousMethod(): string
    {
        return 'Method in anonymous class';
    }
};
$anonymous->anonymousMethod();

// 12. Callable methods
$callable1 = [$methods, 'publicMethod'];
$callable2 = [Methods::class, 'staticMethod'];
$callable3 = $methods->publicMethod(...);

// Dynamic method calls for testing
$calc = new Calculator();
$method = 'add';
$calc->$method(1, 2);

// 13. Return type demonstrations
$void = $methods->returnsVoid(); // void
$string = $methods->returnsString(); // string
$nullableString = $methods->returnsNullableString(); // ?string
$unionType = $methods->returnsUnionType(); // string|int
$mixed = $methods->returnsMixed(); // mixed
$array = $methods->returnsArray(); // array
$self = $methods->returnsSelf(); // self
$generator = $methods->returnsGenerator(); // Generator