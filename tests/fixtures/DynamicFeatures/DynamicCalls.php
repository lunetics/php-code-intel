<?php

namespace TestFixtures\DynamicFeatures;

/**
 * Demonstrates various forms of dynamic function and method calls in PHP
 */
class DynamicCalls
{
    private array $callbacks = [];
    private array $handlers = [];
    
    /**
     * Variable function calls
     */
    public function demonstrateVariableFunctions(): void
    {
        // Built-in functions
        $func = 'strlen';
        $length = $func('Hello'); // Dynamic call to strlen()
        
        $func = 'array_map';
        $result = $func('strtoupper', ['a', 'b', 'c']);
        
        // User-defined functions
        $operation = 'calculate';
        if (function_exists($operation)) {
            $result = $operation(10, 20);
        }
        
        // Namespaced functions
        $func = __NAMESPACE__ . '\\processData';
        $data = $func(['test']);
        
        // Anonymous functions
        $multiply = fn($a, $b) => $a * $b;
        $funcName = 'multiply';
        $result = $$funcName(5, 3);
    }
    
    /**
     * Variable method calls
     */
    public function demonstrateVariableMethods(): void
    {
        $method = 'process';
        $result = $this->$method('data'); // Calls $this->process('data')
        
        // With property access
        $property = 'callbacks';
        $array = $this->$property;
        
        // Chained dynamic calls
        $getter = 'get' . ucfirst($property);
        $items = $this->$getter();
        
        // Dynamic method with dynamic property
        $action = 'handle';
        $type = 'Event';
        $methodName = $action . $type;
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        }
    }
    
    /**
     * Variable static method calls
     */
    public static function demonstrateStaticCalls(): void
    {
        $class = self::class;
        $method = 'staticProcess';
        $result = $class::$method('data');
        
        // With namespace
        $className = __NAMESPACE__ . '\\' . 'DynamicCalls';
        $staticMethod = 'create';
        if (method_exists($className, $staticMethod)) {
            $instance = $className::$staticMethod();
        }
        
        // Late static binding
        $method = 'getInstance';
        $instance = static::$method();
        
        // Variable class constant access
        $constant = 'VERSION';
        if (defined("$class::$constant")) {
            $version = $class::$constant;
        }
    }
    
    /**
     * Dynamic class instantiation
     */
    public function demonstrateDynamicInstantiation(): void
    {
        // Simple instantiation
        $className = 'DateTime';
        $date = new $className();
        
        // With namespace
        $class = __NAMESPACE__ . '\\' . 'DynamicCalls';
        $instance = new $class();
        
        // With constructor arguments
        $className = 'ArrayObject';
        $array = new $className(['a', 'b', 'c'], ArrayObject::ARRAY_AS_PROPS);
        
        // Factory pattern
        $types = ['User', 'Admin', 'Guest'];
        foreach ($types as $type) {
            $className = __NAMESPACE__ . '\\' . $type;
            if (class_exists($className)) {
                $instances[] = new $className();
            }
        }
        
        // Anonymous class
        $className = get_class(new class {});
        $anonymous = new $className();
    }
    
    /**
     * call_user_func demonstrations
     */
    public function demonstrateCallUserFunc(): void
    {
        // Simple function call
        $result = call_user_func('strlen', 'Hello World');
        
        // Method call with array syntax
        $result = call_user_func([$this, 'process'], 'data');
        
        // Static method call
        $result = call_user_func([self::class, 'staticProcess'], 'data');
        
        // Closure call
        $closure = fn($x) => $x * 2;
        $result = call_user_func($closure, 21);
        
        // With namespace
        $result = call_user_func(__NAMESPACE__ . '\\processData', ['test']);
        
        // First-class callable syntax (PHP 8.1+)
        $callable = $this->process(...);
        $result = call_user_func($callable, 'data');
    }
    
    /**
     * call_user_func_array demonstrations
     */
    public function demonstrateCallUserFuncArray(): void
    {
        // With indexed array
        $result = call_user_func_array('sprintf', ['Hello %s', 'World']);
        
        // With associative array (unpacked)
        $args = ['format' => 'Y-m-d', 'timestamp' => time()];
        $result = call_user_func_array('date', array_values($args));
        
        // Method with multiple arguments
        $result = call_user_func_array([$this, 'multipleArgs'], [1, 2, 3, 4, 5]);
        
        // Variadic function
        $numbers = [1, 2, 3, 4, 5];
        $result = call_user_func_array('max', $numbers);
        
        // With splat operator (PHP 5.6+)
        $args = ['Hello', 'World'];
        $result = call_user_func_array('sprintf', ['%s %s', ...$args]);
    }
    
    /**
     * Callback storage and execution
     */
    public function registerCallback(string $name, callable $callback): void
    {
        $this->callbacks[$name] = $callback;
    }
    
    public function executeCallback(string $name, ...$args): mixed
    {
        if (!isset($this->callbacks[$name])) {
            throw new \InvalidArgumentException("Callback '$name' not found");
        }
        
        $callback = $this->callbacks[$name];
        
        // Different ways to execute stored callbacks
        return match(true) {
            is_string($callback) => $callback(...$args),
            is_array($callback) => call_user_func_array($callback, $args),
            is_object($callback) => $callback(...$args),
            default => throw new \RuntimeException('Invalid callback type')
        };
    }
    
    /**
     * Event dispatcher pattern
     */
    public function on(string $event, callable $handler): void
    {
        $this->handlers[$event][] = $handler;
    }
    
    public function trigger(string $event, ...$args): void
    {
        if (!isset($this->handlers[$event])) {
            return;
        }
        
        foreach ($this->handlers[$event] as $handler) {
            // Dynamic handler execution
            call_user_func_array($handler, $args);
        }
    }
    
    /**
     * Dynamic proxy pattern
     */
    public function proxy(object $target, string $method, array $args = []): mixed
    {
        // Before hook
        $beforeMethod = 'before' . ucfirst($method);
        if (method_exists($this, $beforeMethod)) {
            $this->$beforeMethod($target, $args);
        }
        
        // Dynamic method invocation
        $result = call_user_func_array([$target, $method], $args);
        
        // After hook
        $afterMethod = 'after' . ucfirst($method);
        if (method_exists($this, $afterMethod)) {
            $result = $this->$afterMethod($result, $target, $args);
        }
        
        return $result;
    }
    
    /**
     * Safe dynamic invocation with error handling
     */
    public function safeInvoke(callable|string $callback, array $args = [], mixed $default = null): mixed
    {
        try {
            if (is_string($callback)) {
                // Check if it's a function
                if (function_exists($callback)) {
                    return $callback(...$args);
                }
                
                // Check if it's a method on this object
                if (method_exists($this, $callback)) {
                    return $this->$callback(...$args);
                }
                
                // Try to parse class::method syntax
                if (str_contains($callback, '::')) {
                    [$class, $method] = explode('::', $callback, 2);
                    if (method_exists($class, $method)) {
                        return $class::$method(...$args);
                    }
                }
            }
            
            return call_user_func_array($callback, $args);
        } catch (\Throwable $e) {
            return $default;
        }
    }
    
    // Helper methods for demonstrations
    private function process(string $data): string
    {
        return "Processed: $data";
    }
    
    private static function staticProcess(string $data): string
    {
        return "Static processed: $data";
    }
    
    private function multipleArgs(...$args): array
    {
        return $args;
    }
    
    private static function create(): self
    {
        return new self();
    }
    
    private static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
    
    private function handleEvent(): void
    {
        // Event handling logic
    }
    
    private function getCallbacks(): array
    {
        return $this->callbacks;
    }
    
    public const VERSION = '1.0.0';
}

// Helper function for demonstrations
function processData(array $data): array
{
    return array_map('strtoupper', $data);
}

function calculate(int $a, int $b): int
{
    return $a + $b;
}

/**
 * Class demonstrating dynamic factory pattern
 */
class DynamicFactory
{
    private static array $constructors = [];
    
    public static function register(string $type, callable $constructor): void
    {
        self::$constructors[$type] = $constructor;
    }
    
    public static function create(string $type, ...$args): object
    {
        if (!isset(self::$constructors[$type])) {
            throw new \InvalidArgumentException("Unknown type: $type");
        }
        
        $constructor = self::$constructors[$type];
        return $constructor(...$args);
    }
    
    public static function dynamicNew(string $className, array $args = []): object
    {
        // Using variable class name
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class not found: $className");
        }
        
        // Dynamic instantiation with argument unpacking
        return new $className(...$args);
    }
}

/**
 * Example usage of dynamic calls
 */
function demonstrateDynamicCalls(): void
{
    $dynamic = new DynamicCalls();
    
    // Register and execute callbacks
    $dynamic->registerCallback('double', fn($x) => $x * 2);
    $dynamic->registerCallback('greet', fn($name) => "Hello, $name!");
    
    $result1 = $dynamic->executeCallback('double', 21);
    $result2 = $dynamic->executeCallback('greet', 'World');
    
    // Event handling
    $dynamic->on('save', function($data) {
        echo "Saving: " . json_encode($data);
    });
    
    $dynamic->on('save', function($data) {
        log_activity('save', $data);
    });
    
    $dynamic->trigger('save', ['id' => 1, 'name' => 'Test']);
    
    // Safe invocation
    $result = $dynamic->safeInvoke('undefined_function', [], 'default');
    $result = $dynamic->safeInvoke('strlen', ['test']);
    $result = $dynamic->safeInvoke(fn($x) => $x * 2, [21]);
    
    // Factory pattern
    DynamicFactory::register('user', fn($name) => new class($name) {
        public function __construct(private string $name) {}
    });
    
    $user = DynamicFactory::create('user', 'John');
}

// Dummy function for example
function log_activity(string $type, mixed $data): void
{
    // Logging logic
}