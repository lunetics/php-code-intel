<?php

declare(strict_types=1);

namespace TestFixtures\BasicSymbols;

/**
 * Comprehensive demonstration of PHP property types and features
 * Covers PHP 7.4+ through PHP 8.3+ property features
 */
class Properties
{
    // 1. Visibility modifiers: public, protected, private
    public string $publicProperty = 'accessible everywhere';
    protected int $protectedProperty = 42;
    private bool $privateProperty = true;
    
    // 2. Static properties with different visibility
    public static string $publicStaticProperty = 'shared across instances';
    protected static int $protectedStaticProperty = 100;
    private static bool $privateStaticProperty = false;
    
    // 3. Typed properties (PHP 7.4+)
    public int $intProperty;
    public float $floatProperty;
    public bool $boolProperty;
    public string $stringProperty;
    public array $arrayProperty;
    public object $objectProperty;
    public iterable $iterableProperty;
    public mixed $mixedProperty; // PHP 8.0+
    
    // 4. Nullable typed properties
    public ?string $nullableString = null;
    public ?int $nullableInt;
    public ?array $nullableArray = null;
    public ?object $nullableObject;
    public ?self $nullableSelf = null;
    
    // 5. Properties with default values
    public string $stringWithDefault = 'default value';
    public int $intWithDefault = 0;
    public array $arrayWithDefault = [];
    public bool $boolWithDefault = false;
    public ?string $nullableWithDefault = 'not null by default';
    public ?int $nullableIntWithDefault = 42;
    
    // 6. Readonly properties (PHP 8.1+)
    public readonly string $readonlyString;
    protected readonly int $readonlyInt;
    private readonly array $readonlyArray;
    public readonly ?string $readonlyNullable;
    public readonly mixed $readonlyMixed;
    public static readonly string $staticReadonly = 'immutable static';
    
    // Constructor with property promotion (PHP 8.0+)
    public function __construct(
        // 7. Promoted properties in constructor
        public string $promotedPublic,
        protected int $promotedProtected,
        private bool $promotedPrivate,
        public readonly string $promotedReadonly,
        public ?string $promotedNullable = null,
        private readonly array $promotedReadonlyPrivate = [],
        public int $promotedWithDefault = 10,
    ) {
        // Initialize non-promoted typed properties
        $this->intProperty = 1;
        $this->floatProperty = 3.14;
        $this->boolProperty = true;
        $this->stringProperty = 'initialized';
        $this->arrayProperty = ['item1', 'item2'];
        $this->objectProperty = new \stdClass();
        $this->iterableProperty = ['iterable'];
        $this->mixedProperty = 'can be anything';
        
        // Initialize readonly properties (can only be done once)
        $this->readonlyString = 'immutable value';
        $this->readonlyInt = 999;
        $this->readonlyArray = ['readonly', 'data'];
        $this->readonlyNullable = null;
        $this->readonlyMixed = ['mixed', 'readonly'];
    }
    
    // Methods to demonstrate property access
    public function accessPublicProperty(): string
    {
        return $this->publicProperty;
    }
    
    public function accessProtectedProperty(): int
    {
        return $this->protectedProperty;
    }
    
    public function accessPrivateProperty(): bool
    {
        return $this->privateProperty;
    }
    
    public static function accessStaticProperty(): string
    {
        return self::$publicStaticProperty;
    }
    
    public function modifyProperties(): void
    {
        $this->publicProperty = 'modified';
        $this->protectedProperty = 84;
        $this->privateProperty = false;
        
        // Static properties can be modified
        self::$publicStaticProperty = 'modified static';
        self::$protectedStaticProperty = 200;
        self::$privateStaticProperty = true;
        
        // Nullable properties can be set to null
        $this->nullableString = null;
        $this->nullableInt = 123;
        $this->nullableArray = ['not', 'null'];
        
        // Note: readonly properties cannot be modified after initialization
        // $this->readonlyString = 'error'; // This would cause an error
    }
    
    public function demonstrateTypeEnforcement(): void
    {
        // These would cause type errors if uncommented:
        // $this->intProperty = "string"; // TypeError
        // $this->stringProperty = 123; // TypeError
        // $this->arrayProperty = "not an array"; // TypeError
        
        // Nullable properties accept their type or null
        $this->nullableString = "valid string";
        $this->nullableString = null; // Also valid
        
        // Mixed can be anything
        $this->mixedProperty = 123;
        $this->mixedProperty = "string";
        $this->mixedProperty = [];
        $this->mixedProperty = null;
    }
}

// Child class demonstrating property inheritance
class PropertiesChild extends Properties
{
    // Can access public and protected parent properties
    public function accessInheritedProperties(): array
    {
        return [
            'public' => $this->publicProperty,
            'protected' => $this->protectedProperty,
            // 'private' => $this->privateProperty, // Error: cannot access private
            'publicStatic' => self::$publicStaticProperty,
            'protectedStatic' => self::$protectedStaticProperty,
        ];
    }
    
    // Can override parent properties
    public string $publicProperty = 'overridden in child';
    protected int $protectedProperty = 84;
}

// Usage examples
function demonstratePropertyUsage(): void
{
    // Create instance with promoted properties
    $obj = new Properties(
        promotedPublic: 'public value',
        promotedProtected: 42,
        promotedPrivate: true,
        promotedReadonly: 'cannot change this',
        promotedNullable: 'optional value'
    );
    
    // Access public properties directly
    echo $obj->publicProperty . PHP_EOL;
    echo $obj->promotedPublic . PHP_EOL;
    
    // Access static properties
    echo Properties::$publicStaticProperty . PHP_EOL;
    
    // Modify mutable properties
    $obj->publicProperty = 'new value';
    $obj->intProperty = 999;
    $obj->nullableString = 'no longer null';
    
    // Work with nullable properties
    if ($obj->nullableInt === null) {
        $obj->nullableInt = 100;
    }
    
    // Access readonly properties (read-only after construction)
    echo $obj->readonlyString . PHP_EOL;
    echo $obj->promotedReadonly . PHP_EOL;
    // $obj->readonlyString = 'error'; // This would fail
    
    // Type enforcement
    $obj->stringProperty = 'only strings allowed';
    $obj->intProperty = 123; // Only integers
    $obj->arrayProperty = ['array', 'elements'];
    
    // Nullable type enforcement
    $obj->nullableString = 'string value';
    $obj->nullableString = null; // Also valid
    
    // Mixed type accepts anything
    $obj->mixedProperty = 'string';
    $obj->mixedProperty = 123;
    $obj->mixedProperty = ['array'];
    $obj->mixedProperty = new \stdClass();
    
    // Inheritance example
    $child = new PropertiesChild(
        promotedPublic: 'child public',
        promotedProtected: 100,
        promotedPrivate: false,
        promotedReadonly: 'child readonly',
    );
    
    // Child can access inherited public property
    echo $child->publicProperty . PHP_EOL; // Shows overridden value
    
    // Static property is shared across all instances
    Properties::$publicStaticProperty = 'changed globally';
    echo Properties::$publicStaticProperty . PHP_EOL;
    echo PropertiesChild::$publicStaticProperty . PHP_EOL; // Same value
}

// Advanced property features for PHP 8.0+
class AdvancedProperties
{
    // Union types (PHP 8.0+)
    public string|int $unionType;
    public string|int|null $nullableUnionType = null;
    
    // Property with class type
    public Properties $typedClassProperty;
    public ?Properties $nullableClassProperty = null;
    
    // Array with specific type declaration (for documentation)
    /** @var string[] */
    public array $stringArray = [];
    
    /** @var array<string, int> */
    public array $associativeArray = [];
    
    public function __construct()
    {
        $this->unionType = 'string or int';
        $this->typedClassProperty = new Properties(
            promotedPublic: 'nested',
            promotedProtected: 1,
            promotedPrivate: true,
            promotedReadonly: 'nested readonly'
        );
    }
}

// Enum property example (PHP 8.1+)
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class EnumProperties
{
    public Status $status;
    public ?Status $nullableStatus = null;
    public readonly Status $readonlyStatus;
    
    public function __construct(
        public Status $promotedStatus = Status::ACTIVE
    ) {
        $this->status = Status::ACTIVE;
        $this->readonlyStatus = Status::PENDING;
    }
}

// Intersection types (PHP 8.1+)
interface Loggable {}
interface Cacheable {}

class IntersectionTypeExample implements Loggable, Cacheable
{
    // Property must implement both interfaces
    public Loggable&Cacheable $intersectionProperty;
    
    public function __construct()
    {
        $this->intersectionProperty = $this; // This class implements both
    }
}

// Note: PHP 8.4+ may include property hooks (get/set), but as of PHP 8.3,
// this feature is not yet available. Here's what it might look like:
/*
class FutureProperties
{
    // Hypothetical PHP 8.4+ syntax
    public string $computed {
        get => strtoupper($this->value);
        set => $this->value = strtolower($value);
    }
    
    private string $value = '';
}
*/

// Run the demonstration
demonstratePropertyUsage();