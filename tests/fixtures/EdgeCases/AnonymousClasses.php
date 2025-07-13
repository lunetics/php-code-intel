<?php

declare(strict_types=1);

namespace TestFixtures\EdgeCases;

// Simple anonymous class
$simple = new class {
    public function getName(): string
    {
        return 'SimpleAnonymous';
    }
};

// Anonymous class extending base class
abstract class BaseClass
{
    protected string $baseProperty = 'base';
    
    abstract public function abstractMethod(): string;
}

$extending = new class extends BaseClass {
    public function abstractMethod(): string
    {
        return $this->baseProperty . ' extended';
    }
    
    public function getParentProperty(): string
    {
        return $this->baseProperty;
    }
};

// Anonymous class implementing interface
interface TestInterface
{
    public function testMethod(): string;
}

interface SecondInterface
{
    public function secondMethod(): int;
}

$implementing = new class implements TestInterface, SecondInterface {
    public function testMethod(): string
    {
        return 'implemented';
    }
    
    public function secondMethod(): int
    {
        return 42;
    }
};

// Anonymous class with constructor
$withConstructor = new class('test', 123) {
    private string $name;
    private int $value;
    
    public function __construct(string $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getValue(): int
    {
        return $this->value;
    }
};

// Anonymous class using traits
trait HelperTrait
{
    public function helper(): string
    {
        return 'helped';
    }
}

trait SecondTrait
{
    protected int $counter = 0;
    
    public function increment(): void
    {
        $this->counter++;
    }
}

$withTraits = new class {
    use HelperTrait, SecondTrait;
    
    public function combined(): string
    {
        $this->increment();
        return $this->helper() . ' ' . $this->counter;
    }
};

// Nested anonymous classes
$outer = new class {
    private string $outerProp = 'outer';
    
    public function createInner(): object
    {
        $outerRef = $this;
        
        return new class($outerRef) {
            private object $parent;
            
            public function __construct(object $parent)
            {
                $this->parent = $parent;
            }
            
            public function createDeeper(): object
            {
                return new class {
                    public function getDepth(): int
                    {
                        return 3;
                    }
                };
            }
            
            public function accessParent(): string
            {
                return 'inner accessing outer';
            }
        };
    }
};

// Complex anonymous class with everything combined
$complex = new class('complex', 100) extends BaseClass implements TestInterface {
    use HelperTrait;
    
    private string $name;
    private int $value;
    public const CONSTANT = 'ANON_CONST';
    private static int $staticCounter = 0;
    
    public function __construct(string $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
        self::$staticCounter++;
    }
    
    public function abstractMethod(): string
    {
        return $this->name . ' ' . $this->baseProperty;
    }
    
    public function testMethod(): string
    {
        return $this->helper() . ' in complex';
    }
    
    public static function getStaticCounter(): int
    {
        return self::$staticCounter;
    }
    
    public function __toString(): string
    {
        return sprintf('%s(%d)', $this->name, $this->value);
    }
    
    public function __get(string $prop): mixed
    {
        return match($prop) {
            'computed' => $this->value * 2,
            default => null
        };
    }
    
    public function __set(string $prop, mixed $value): void
    {
        if ($prop === 'value' && is_int($value)) {
            $this->value = $value;
        }
    }
    
    public function __isset(string $prop): bool
    {
        return in_array($prop, ['name', 'value', 'computed']);
    }
};

// Anonymous class in array
$anonymousArray = [
    'first' => new class {
        public function identify(): string
        {
            return 'first';
        }
    },
    'second' => new class {
        public function identify(): string
        {
            return 'second';
        }
    }
];

// Anonymous class as function parameter
function processAnonymous(object $obj): string
{
    if (method_exists($obj, 'process')) {
        return $obj->process();
    }
    return 'no process method';
}

$result = processAnonymous(new class {
    public function process(): string
    {
        return 'processed';
    }
});

// Anonymous class with property initialization (PHP 8.1+)
class Container
{
    public object $service;
    
    public function __construct()
    {
        $this->service = new class {
            public function serve(): string
            {
                return 'serving';
            }
        };
    }
}

// Return anonymous class from function
function createAnonymousService(string $type): object
{
    return match($type) {
        'logger' => new class {
            public function log(string $message): void
            {
                echo "[LOG] $message\n";
            }
        },
        'cache' => new class {
            private array $data = [];
            
            public function set(string $key, mixed $value): void
            {
                $this->data[$key] = $value;
            }
            
            public function get(string $key): mixed
            {
                return $this->data[$key] ?? null;
            }
        },
        default => new class {
            public function noop(): void {}
        }
    };
}

// Anonymous class with static properties and methods
$withStatic = new class {
    private static array $instances = [];
    public static string $sharedData = 'shared';
    
    public function __construct()
    {
        self::$instances[] = $this;
    }
    
    public static function getInstanceCount(): int
    {
        return count(self::$instances);
    }
    
    public static function factory(): self
    {
        return new self();
    }
};

// Edge case: Anonymous class extending another anonymous class (not directly possible)
// But we can demonstrate inheritance chain
$base = new class {
    public function baseMethod(): string
    {
        return 'base';
    }
};

// Global scope anonymous class
$GLOBALS['anonymousGlobal'] = new class {
    public function isGlobal(): bool
    {
        return true;
    }
};