<?php

declare(strict_types=1);

namespace TestFixtures\ModernPHP;

/**
 * Demonstrates various typed property features in PHP 7.4+, 8.0+, 8.1+, and 8.2+
 */
class TypedProperties
{
    // Scalar typed properties (PHP 7.4+)
    public int $intProperty;
    public float $floatProperty;
    public string $stringProperty;
    public bool $boolProperty;
    
    // Nullable scalar types
    public ?int $nullableInt = null;
    public ?string $nullableString = null;
    
    // Object typed properties
    public \DateTime $dateTime;
    public \stdClass $stdClass;
    public TypedProperties $selfReference;
    
    // Array and iterable types
    public array $simpleArray = [];
    public iterable $iterableProperty;
    
    // Mixed type (PHP 8.0+)
    public mixed $mixedProperty;
    
    // Union types (PHP 8.0+)
    public int|string $intOrString;
    public array|object $arrayOrObject;
    public \DateTime|\DateTimeImmutable $dateUnion;
    public int|float|null $numericOrNull;
    
    // Intersection types (PHP 8.1+)
    public \Traversable&\Countable $traversableCountable;
    public \Iterator&\ArrayAccess $iteratorArrayAccess;
    
    // DNF (Disjunctive Normal Form) types (PHP 8.2+)
    public (\Traversable&\Countable)|array $dnfArrayLike;
    public (\Iterator&\Countable)|(\IteratorAggregate&\Countable) $dnfIterator;
    public (TypeA&TypeB)|null $dnfNullable;
    
    // Static typed properties
    public static int $staticInt = 42;
    public static ?string $staticNullableString = null;
    
    // Protected and private typed properties
    protected float $protectedFloat = 3.14;
    private bool $privateBool = true;
    
    // Readonly properties (PHP 8.1+)
    public readonly string $readonlyString;
    public readonly int $readonlyInt;
    public readonly mixed $readonlyMixed;
    
    // Readonly with union types
    public readonly int|string $readonlyUnion;
    
    // Readonly with intersection types
    public readonly \Traversable&\Countable $readonlyIntersection;
    
    // Properties with default values
    public int $intWithDefault = 100;
    public string $stringWithDefault = "default";
    public array $arrayWithDefault = ['foo', 'bar'];
    public bool $boolWithDefault = false;
    
    // Complex union types with null
    public int|string|array|null $complexUnion = null;
    
    // Union with false (PHP 8.0+)
    public string|false $stringOrFalse = false;
    
    // Union with true (PHP 8.2+)
    public string|true $stringOrTrue = true;
    
    // Literal types in union (PHP 8.2+)
    public string|false|null $stringFalseOrNull = null;
    
    public function __construct()
    {
        // Initialize required properties
        $this->intProperty = 0;
        $this->floatProperty = 0.0;
        $this->stringProperty = '';
        $this->boolProperty = false;
        $this->dateTime = new \DateTime();
        $this->stdClass = new \stdClass();
        $this->selfReference = $this;
        $this->iterableProperty = [];
        $this->mixedProperty = null;
        $this->intOrString = 0;
        $this->arrayOrObject = [];
        $this->dateUnion = new \DateTime();
        $this->readonlyString = 'immutable';
        $this->readonlyInt = 42;
        $this->readonlyMixed = 'anything';
        $this->readonlyUnion = 'string value';
    }
    
    // Methods demonstrating typed property usage
    public function setIntOrString(int|string $value): void
    {
        $this->intOrString = $value;
    }
    
    public function getIntOrString(): int|string
    {
        return $this->intOrString;
    }
    
    public function setDnfArrayLike((\Traversable&\Countable)|array $value): void
    {
        $this->dnfArrayLike = $value;
    }
    
    public function getDnfArrayLike(): (\Traversable&\Countable)|array
    {
        return $this->dnfArrayLike;
    }
}

// Helper interfaces for DNF types
interface TypeA {}
interface TypeB {}

// Example class implementing multiple interfaces for intersection types
class TraversableCountableExample implements \Traversable, \Countable
{
    private array $data = [];
    
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }
    
    public function count(): int
    {
        return count($this->data);
    }
}

// Class with typed properties in inheritance
class ExtendedTypedProperties extends TypedProperties
{
    // Additional typed properties in child class
    public object $genericObject;
    public callable $callableProperty;
    
    // Covariant property type (PHP 7.4+)
    public ?\DateTime $dateTime = null;
    
    // More specific union types
    public int|string|bool $scalarUnion;
    
    // Property promotion in constructor (PHP 8.0+)
    public function __construct(
        public string $promotedString = 'promoted',
        public readonly int $promotedReadonlyInt = 100,
        private bool $promotedPrivateBool = true
    ) {
        parent::__construct();
        $this->genericObject = new \stdClass();
        $this->callableProperty = fn() => null;
        $this->scalarUnion = true;
    }
}

// Class demonstrating property initialization patterns
class PropertyInitializationPatterns
{
    // Uninitialized typed properties
    public int $uninitializedInt;
    public string $uninitializedString;
    public object $uninitializedObject;
    
    // Late initialization pattern
    private ?string $lazyLoadedData = null;
    
    public function getLazyLoadedData(): string
    {
        if ($this->lazyLoadedData === null) {
            $this->lazyLoadedData = $this->loadData();
        }
        return $this->lazyLoadedData;
    }
    
    private function loadData(): string
    {
        return 'loaded data';
    }
    
    // Property hooks pattern for validation
    private int $_validatedInt;
    
    public function setValidatedInt(int $value): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Value must be non-negative');
        }
        $this->_validatedInt = $value;
    }
    
    public function getValidatedInt(): int
    {
        return $this->_validatedInt ?? 0;
    }
}