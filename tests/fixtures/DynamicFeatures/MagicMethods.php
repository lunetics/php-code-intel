<?php

namespace TestFixtures\DynamicFeatures;

/**
 * Demonstrates PHP magic methods for dynamic behavior
 */
class MagicMethods
{
    private array $data = [];
    private array $methods = [];
    
    /**
     * Handle dynamic property access
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
    
    /**
     * Handle dynamic property assignment
     */
    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }
    
    /**
     * Check if dynamic property exists
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
    
    /**
     * Unset dynamic property
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }
    
    /**
     * Handle dynamic method calls
     */
    public function __call(string $method, array $arguments): mixed
    {
        // Check for registered methods
        if (isset($this->methods[$method])) {
            return call_user_func_array($this->methods[$method], $arguments);
        }
        
        // Check for getter/setter pattern
        if (str_starts_with($method, 'get')) {
            $property = lcfirst(substr($method, 3));
            return $this->__get($property);
        }
        
        if (str_starts_with($method, 'set')) {
            $property = lcfirst(substr($method, 3));
            $this->__set($property, $arguments[0] ?? null);
            return $this;
        }
        
        throw new \BadMethodCallException("Method $method does not exist");
    }
    
    /**
     * Handle static dynamic calls
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        // Factory pattern
        if (str_starts_with($method, 'create')) {
            $type = lcfirst(substr($method, 6));
            return new static(['type' => $type, ...$arguments[0] ?? []]);
        }
        
        // Static proxy to instance
        if (str_starts_with($method, 'static')) {
            $instance = new static();
            $instanceMethod = lcfirst(substr($method, 6));
            return $instance->$instanceMethod(...$arguments);
        }
        
        throw new \BadMethodCallException("Static method $method does not exist");
    }
    
    /**
     * String representation
     */
    public function __toString(): string
    {
        return json_encode($this->data);
    }
    
    /**
     * Make object callable
     */
    public function __invoke(...$args): mixed
    {
        if (count($args) === 0) {
            return $this->data;
        }
        
        if (count($args) === 1) {
            return $this->__get($args[0]);
        }
        
        $this->__set($args[0], $args[1]);
        return $this;
    }
    
    /**
     * Custom serialization
     */
    public function __serialize(): array
    {
        return [
            'data' => $this->data,
            'methods' => array_keys($this->methods),
            'timestamp' => time()
        ];
    }
    
    /**
     * Custom unserialization
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data['data'] ?? [];
        // Methods need to be re-registered after unserialization
    }
    
    /**
     * Clone behavior
     */
    public function __clone(): void
    {
        $this->data = array_map(function ($value) {
            return is_object($value) ? clone $value : $value;
        }, $this->data);
    }
    
    /**
     * Debug information
     */
    public function __debugInfo(): array
    {
        return [
            'properties' => $this->data,
            'methodCount' => count($this->methods),
            'className' => static::class
        ];
    }
    
    /**
     * Register a dynamic method
     */
    public function registerMethod(string $name, callable $callback): void
    {
        $this->methods[$name] = $callback;
    }
    
    /**
     * Constructor accepting initial data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}

/**
 * Trait demonstrating magic methods in traits
 */
trait MagicTrait
{
    private array $traitData = [];
    
    public function __get(string $name): mixed
    {
        if (str_starts_with($name, 'trait')) {
            return $this->traitData[$name] ?? null;
        }
        return parent::__get($name) ?? null;
    }
    
    public function __set(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'trait')) {
            $this->traitData[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }
}

/**
 * Class using the magic trait
 */
class MagicWithTrait extends MagicMethods
{
    use MagicTrait;
}

/**
 * Interface for objects with magic toString
 */
interface StringableInterface
{
    public function __toString(): string;
}

/**
 * Abstract class with magic methods
 */
abstract class AbstractMagic implements StringableInterface
{
    protected array $config = [];
    
    public function __get(string $name): mixed
    {
        return $this->config[$name] ?? $this->getDefault($name);
    }
    
    abstract protected function getDefault(string $name): mixed;
}

/**
 * Concrete implementation
 */
class ConcreteMagic extends AbstractMagic
{
    protected function getDefault(string $name): mixed
    {
        return match($name) {
            'timeout' => 30,
            'retries' => 3,
            default => null
        };
    }
    
    public function __toString(): string
    {
        return sprintf('%s[%s]', static::class, json_encode($this->config));
    }
}

// Example usage demonstrating dynamic behavior
function demonstrateMagicMethods(): void
{
    $magic = new MagicMethods();
    
    // Dynamic properties
    $magic->name = 'Dynamic';
    $magic->value = 42;
    echo $magic->name; // Triggers __get
    
    // Dynamic methods via __call
    $magic->setTitle('Magic Methods');
    echo $magic->getTitle(); // Returns 'Magic Methods'
    
    // Static factory via __callStatic
    $user = MagicMethods::createUser(['name' => 'John', 'age' => 30]);
    
    // Object as function via __invoke
    $magic('key', 'value'); // Sets property
    $value = $magic('key'); // Gets property
    $all = $magic(); // Gets all data
    
    // String conversion
    echo $magic; // Triggers __toString
    
    // Isset/unset
    if (isset($magic->name)) {
        unset($magic->name);
    }
    
    // Register dynamic method
    $magic->registerMethod('double', fn($x) => $x * 2);
    echo $magic->double(21); // Returns 42
    
    // Cloning
    $clone = clone $magic;
    
    // Serialization
    $serialized = serialize($magic);
    $unserialized = unserialize($serialized);
    
    // Debug info
    var_dump($magic); // Uses __debugInfo
}

// Complex example with method chaining
class FluentMagic extends MagicMethods
{
    public function __call(string $method, array $arguments): mixed
    {
        if (str_starts_with($method, 'with')) {
            $property = lcfirst(substr($method, 4));
            $this->__set($property, $arguments[0] ?? null);
            return $this; // Enable chaining
        }
        
        return parent::__call($method, $arguments);
    }
}

// Usage of fluent interface
function demonstrateFluentMagic(): void
{
    $fluent = new FluentMagic();
    $result = $fluent
        ->withName('Fluent')
        ->withVersion('1.0')
        ->withEnabled(true)
        ->getName(); // Chain multiple calls
}