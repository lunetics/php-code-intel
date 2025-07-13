<?php

namespace TestFixtures\DynamicFeatures;

/**
 * Demonstrates PHP's Reflection API for runtime introspection and manipulation
 */
class Reflection
{
    private string $privateProperty = 'private';
    protected string $protectedProperty = 'protected';
    public string $publicProperty = 'public';
    
    private const PRIVATE_CONST = 'private_const';
    protected const PROTECTED_CONST = 'protected_const';
    public const PUBLIC_CONST = 'public_const';
    
    /**
     * ReflectionClass usage examples
     */
    public function demonstrateReflectionClass(): void
    {
        // Basic class reflection
        $reflection = new \ReflectionClass($this);
        
        // Get class information
        $className = $reflection->getName();
        $shortName = $reflection->getShortName();
        $namespace = $reflection->getNamespaceName();
        $fileName = $reflection->getFileName();
        
        // Check class type
        $isAbstract = $reflection->isAbstract();
        $isFinal = $reflection->isFinal();
        $isInterface = $reflection->isInterface();
        $isTrait = $reflection->isTrait();
        
        // Get parent class
        $parent = $reflection->getParentClass();
        if ($parent) {
            $parentName = $parent->getName();
        }
        
        // Get interfaces
        $interfaces = $reflection->getInterfaces();
        $interfaceNames = $reflection->getInterfaceNames();
        
        // Get traits
        $traits = $reflection->getTraits();
        $traitNames = $reflection->getTraitNames();
        
        // Dynamic instantiation
        $instance = $reflection->newInstance();
        $instanceWithArgs = $reflection->newInstanceArgs(['arg1', 'arg2']);
        
        // Without constructor
        $instanceWithoutConstructor = $reflection->newInstanceWithoutConstructor();
    }
    
    /**
     * ReflectionMethod usage examples
     */
    public function demonstrateReflectionMethod(): void
    {
        $reflection = new \ReflectionClass($this);
        
        // Get all methods
        $methods = $reflection->getMethods();
        
        // Filter by visibility
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $privateMethods = $reflection->getMethods(\ReflectionMethod::IS_PRIVATE);
        $protectedMethods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
        
        // Get specific method
        if ($reflection->hasMethod('privateMethod')) {
            $method = $reflection->getMethod('privateMethod');
            
            // Method information
            $methodName = $method->getName();
            $isPublic = $method->isPublic();
            $isPrivate = $method->isPrivate();
            $isProtected = $method->isProtected();
            $isStatic = $method->isStatic();
            $isFinal = $method->isFinal();
            $isAbstract = $method->isAbstract();
            
            // Get parameters
            $parameters = $method->getParameters();
            $numberOfParameters = $method->getNumberOfParameters();
            $numberOfRequiredParameters = $method->getNumberOfRequiredParameters();
            
            // Return type
            if ($method->hasReturnType()) {
                $returnType = $method->getReturnType();
                $returnTypeName = $returnType->getName();
            }
            
            // Make private method accessible
            $method->setAccessible(true);
            
            // Invoke method dynamically
            $result = $method->invoke($this, 'arg1', 'arg2');
            $resultWithArgs = $method->invokeArgs($this, ['arg1', 'arg2']);
        }
    }
    
    /**
     * ReflectionProperty usage examples
     */
    public function demonstrateReflectionProperty(): void
    {
        $reflection = new \ReflectionClass($this);
        
        // Get all properties
        $properties = $reflection->getProperties();
        
        // Filter by visibility
        $publicProps = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $privateProps = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);
        $protectedProps = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        $staticProps = $reflection->getProperties(\ReflectionProperty::IS_STATIC);
        
        // Get specific property
        if ($reflection->hasProperty('privateProperty')) {
            $property = $reflection->getProperty('privateProperty');
            
            // Property information
            $propName = $property->getName();
            $isPublic = $property->isPublic();
            $isPrivate = $property->isPrivate();
            $isProtected = $property->isProtected();
            $isStatic = $property->isStatic();
            
            // Type information
            if ($property->hasType()) {
                $type = $property->getType();
                $typeName = $type->getName();
                $allowsNull = $type->allowsNull();
            }
            
            // Make private property accessible
            $property->setAccessible(true);
            
            // Get and set values dynamically
            $value = $property->getValue($this);
            $property->setValue($this, 'new value');
            
            // Check if initialized
            $isInitialized = $property->isInitialized($this);
            
            // Get attributes (PHP 8+)
            $attributes = $property->getAttributes();
        }
    }
    
    /**
     * ReflectionParameter usage examples
     */
    public function demonstrateReflectionParameter(mixed $required, ?string $optional = null, int ...$variadic): void
    {
        $reflection = new \ReflectionMethod($this, 'demonstrateReflectionParameter');
        $parameters = $reflection->getParameters();
        
        foreach ($parameters as $param) {
            // Parameter information
            $name = $param->getName();
            $position = $param->getPosition();
            
            // Type information
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof \ReflectionUnionType) {
                    $types = $type->getTypes();
                    $typeNames = array_map(fn($t) => $t->getName(), $types);
                } else {
                    $typeName = $type->getName();
                }
            }
            
            // Default value
            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
            }
            
            // Parameter attributes
            $isOptional = $param->isOptional();
            $isVariadic = $param->isVariadic();
            $isPassedByReference = $param->isPassedByReference();
            $canBePassedByValue = $param->canBePassedByValue();
        }
    }
    
    /**
     * Dynamic object copying using reflection
     */
    public function deepCopy(object $object): object
    {
        $reflection = new \ReflectionClass($object);
        $copy = $reflection->newInstanceWithoutConstructor();
        
        // Copy all properties including private ones
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if ($property->isInitialized($object)) {
                $value = $property->getValue($object);
                
                // Deep copy objects
                if (is_object($value)) {
                    $value = $this->deepCopy($value);
                } elseif (is_array($value)) {
                    $value = array_map(function ($item) {
                        return is_object($item) ? $this->deepCopy($item) : $item;
                    }, $value);
                }
                
                $property->setValue($copy, $value);
            }
        }
        
        return $copy;
    }
    
    /**
     * Dynamic proxy creation
     */
    public function createProxy(object $target): object
    {
        $targetReflection = new \ReflectionClass($target);
        
        return new class($target, $targetReflection) {
            private object $target;
            private \ReflectionClass $reflection;
            private array $interceptors = [];
            
            public function __construct(object $target, \ReflectionClass $reflection)
            {
                $this->target = $target;
                $this->reflection = $reflection;
            }
            
            public function addInterceptor(string $method, callable $interceptor): void
            {
                $this->interceptors[$method][] = $interceptor;
            }
            
            public function __call(string $method, array $args): mixed
            {
                // Before interceptors
                if (isset($this->interceptors[$method])) {
                    foreach ($this->interceptors[$method] as $interceptor) {
                        $interceptor('before', $method, $args);
                    }
                }
                
                // Call original method
                if ($this->reflection->hasMethod($method)) {
                    $reflectionMethod = $this->reflection->getMethod($method);
                    $reflectionMethod->setAccessible(true);
                    $result = $reflectionMethod->invokeArgs($this->target, $args);
                } else {
                    $result = $this->target->$method(...$args);
                }
                
                // After interceptors
                if (isset($this->interceptors[$method])) {
                    foreach ($this->interceptors[$method] as $interceptor) {
                        $result = $interceptor('after', $method, $args, $result);
                    }
                }
                
                return $result;
            }
            
            public function __get(string $property): mixed
            {
                if ($this->reflection->hasProperty($property)) {
                    $reflectionProperty = $this->reflection->getProperty($property);
                    $reflectionProperty->setAccessible(true);
                    return $reflectionProperty->getValue($this->target);
                }
                
                return $this->target->$property;
            }
            
            public function __set(string $property, mixed $value): void
            {
                if ($this->reflection->hasProperty($property)) {
                    $reflectionProperty = $this->reflection->getProperty($property);
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($this->target, $value);
                } else {
                    $this->target->$property = $value;
                }
            }
        };
    }
    
    /**
     * Method invocation with named parameters
     */
    public function invokeWithNamedParams(object $object, string $method, array $namedParams): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $params = $reflection->getParameters();
        $args = [];
        
        foreach ($params as $param) {
            $name = $param->getName();
            
            if (array_key_exists($name, $namedParams)) {
                $args[] = $namedParams[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[] = null;
            } else {
                throw new \InvalidArgumentException("Missing required parameter: $name");
            }
        }
        
        return $reflection->invokeArgs($object, $args);
    }
    
    /**
     * Get all class constants by visibility
     */
    public function getConstantsByVisibility(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $constants = $reflection->getReflectionConstants();
        
        $result = [
            'public' => [],
            'protected' => [],
            'private' => []
        ];
        
        foreach ($constants as $constant) {
            $name = $constant->getName();
            $value = $constant->getValue();
            
            if ($constant->isPublic()) {
                $result['public'][$name] = $value;
            } elseif ($constant->isProtected()) {
                $result['protected'][$name] = $value;
            } elseif ($constant->isPrivate()) {
                $result['private'][$name] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Dynamic trait usage detection
     */
    public function analyzeTrait(string $traitName): array
    {
        $reflection = new \ReflectionClass($traitName);
        
        if (!$reflection->isTrait()) {
            throw new \InvalidArgumentException("$traitName is not a trait");
        }
        
        return [
            'methods' => array_map(fn($m) => $m->getName(), $reflection->getMethods()),
            'properties' => array_map(fn($p) => $p->getName(), $reflection->getProperties()),
            'usedTraits' => $reflection->getTraitNames()
        ];
    }
    
    // Private methods for testing reflection
    private function privateMethod(string $arg1, string $arg2): string
    {
        return "Private: $arg1, $arg2";
    }
    
    protected function protectedMethod(): string
    {
        return "Protected method";
    }
    
    public function publicMethod(): string
    {
        return "Public method";
    }
}

/**
 * Class with attributes for reflection testing
 */
#[\Attribute]
class CustomAttribute
{
    public function __construct(public string $value) {}
}

class AttributedClass
{
    #[CustomAttribute("property")]
    private string $attributedProperty = 'value';
    
    #[CustomAttribute("method")]
    public function attributedMethod(): void {}
}

/**
 * Example usage of reflection
 */
function demonstrateReflection(): void
{
    $reflection = new Reflection();
    
    // Create a proxy with logging
    $proxy = $reflection->createProxy($reflection);
    $proxy->addInterceptor('publicMethod', function($phase, $method, $args, $result = null) {
        if ($phase === 'before') {
            echo "Calling $method with args: " . json_encode($args) . "\n";
        } else {
            echo "Method $method returned: $result\n";
            return $result;
        }
    });
    
    // Use the proxy
    $result = $proxy->publicMethod();
    
    // Deep copy an object
    $original = new \stdClass();
    $original->nested = new \stdClass();
    $original->nested->value = 'test';
    
    $copy = $reflection->deepCopy($original);
    $copy->nested->value = 'modified';
    
    // Original remains unchanged
    assert($original->nested->value === 'test');
    
    // Invoke with named parameters
    $object = new class {
        public function method(string $required, int $optional = 10, ?bool $nullable = null): string
        {
            return "Required: $required, Optional: $optional, Nullable: " . var_export($nullable, true);
        }
    };
    
    $result = $reflection->invokeWithNamedParams($object, 'method', [
        'nullable' => true,
        'required' => 'test'
        // 'optional' will use default value
    ]);
    
    // Analyze constants
    $constants = $reflection->getConstantsByVisibility(Reflection::class);
    
    // Check for attributes
    $attrReflection = new \ReflectionClass(AttributedClass::class);
    $property = $attrReflection->getProperty('attributedProperty');
    $attributes = $property->getAttributes(CustomAttribute::class);
    
    if (!empty($attributes)) {
        $attribute = $attributes[0]->newInstance();
        echo "Attribute value: " . $attribute->value . "\n";
    }
}

/**
 * Utility class for complete method analysis
 */
class MethodAnalyzer
{
    public static function analyze(string $class, string $method): array
    {
        $reflection = new \ReflectionMethod($class, $method);
        
        $analysis = [
            'name' => $reflection->getName(),
            'class' => $reflection->getDeclaringClass()->getName(),
            'visibility' => $reflection->isPublic() ? 'public' : 
                           ($reflection->isProtected() ? 'protected' : 'private'),
            'static' => $reflection->isStatic(),
            'final' => $reflection->isFinal(),
            'abstract' => $reflection->isAbstract(),
            'parameters' => [],
            'returnType' => null,
            'docComment' => $reflection->getDocComment()
        ];
        
        // Analyze parameters
        foreach ($reflection->getParameters() as $param) {
            $paramInfo = [
                'name' => $param->getName(),
                'type' => $param->hasType() ? $param->getType()->getName() : null,
                'optional' => $param->isOptional(),
                'defaultValue' => $param->isDefaultValueAvailable() ? 
                                 $param->getDefaultValue() : null,
                'variadic' => $param->isVariadic(),
                'byReference' => $param->isPassedByReference()
            ];
            $analysis['parameters'][] = $paramInfo;
        }
        
        // Analyze return type
        if ($reflection->hasReturnType()) {
            $returnType = $reflection->getReturnType();
            $analysis['returnType'] = [
                'type' => $returnType instanceof \ReflectionUnionType ?
                         array_map(fn($t) => $t->getName(), $returnType->getTypes()) :
                         $returnType->getName(),
                'nullable' => $returnType->allowsNull()
            ];
        }
        
        return $analysis;
    }
}