<?php

declare(strict_types=1);

namespace TestFixtures\ModernPHP;

use Attribute;

/**
 * Demonstrates PHP 8.0+ Attributes (annotations) features
 */

// Simple attribute without parameters
#[Attribute]
class SimpleAttribute {}

// Attribute with parameters
#[Attribute]
class ConfigurableAttribute
{
    public function __construct(
        public string $name,
        public int $priority = 0,
        public array $tags = []
    ) {}
}

// Attribute with specific targets
#[Attribute(Attribute::TARGET_CLASS)]
class ClassOnlyAttribute {}

#[Attribute(Attribute::TARGET_METHOD)]
class MethodOnlyAttribute {}

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyOnlyAttribute {}

#[Attribute(Attribute::TARGET_PARAMETER)]
class ParameterOnlyAttribute {}

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class ConstantOnlyAttribute {}

#[Attribute(Attribute::TARGET_FUNCTION)]
class FunctionOnlyAttribute {}

// Attribute with multiple targets
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ClassOrMethodAttribute {}

// Repeatable attribute
#[Attribute(Attribute::IS_REPEATABLE)]
class RepeatableAttribute
{
    public function __construct(public string $value) {}
}

// Attribute that can be inherited
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class InheritableAttribute {}

// Built-in style attributes
#[Attribute(Attribute::TARGET_METHOD)]
class Deprecated
{
    public function __construct(
        public string $reason = '',
        public ?string $since = null,
        public ?string $replacement = null
    ) {}
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Api
{
    public function __construct(
        public string $version,
        public array $endpoints = []
    ) {}
}

// Class demonstrating various attribute usages
#[SimpleAttribute]
#[ConfigurableAttribute(name: 'TestClass', priority: 10, tags: ['test', 'example'])]
#[Api(version: '1.0', endpoints: ['/users', '/posts'])]
#[RepeatableAttribute('first')]
#[RepeatableAttribute('second')]
class AttributedClass
{
    #[PropertyOnlyAttribute]
    #[ConfigurableAttribute(name: 'id', priority: 1)]
    public int $id;
    
    #[PropertyOnlyAttribute]
    private string $secret;
    
    #[ConstantOnlyAttribute]
    #[ConfigurableAttribute(name: 'version_constant')]
    public const VERSION = '1.0.0';
    
    #[ConstantOnlyAttribute]
    private const SECRET_KEY = 'hidden';
    
    #[SimpleAttribute]
    #[MethodOnlyAttribute]
    #[ConfigurableAttribute(name: 'constructor', priority: 100)]
    public function __construct(
        #[ParameterOnlyAttribute]
        #[ConfigurableAttribute(name: 'param1')]
        int $param1,
        
        #[ParameterOnlyAttribute]
        string $param2 = 'default'
    ) {
        $this->id = $param1;
        $this->secret = $param2;
    }
    
    #[SimpleAttribute]
    #[MethodOnlyAttribute]
    #[Deprecated(reason: 'Use newMethod() instead', since: '2.0', replacement: 'newMethod')]
    public function oldMethod(): void
    {
        // Deprecated method
    }
    
    #[MethodOnlyAttribute]
    #[ConfigurableAttribute(name: 'newMethod', priority: 5)]
    #[RepeatableAttribute('method-tag-1')]
    #[RepeatableAttribute('method-tag-2')]
    public function newMethod(
        #[ParameterOnlyAttribute] int $value
    ): string {
        return "Value: $value";
    }
    
    #[Api(version: '2.0', endpoints: ['/v2/data'])]
    public static function staticMethod(): array
    {
        return [];
    }
}

// Global function with attributes
#[FunctionOnlyAttribute]
#[ConfigurableAttribute(name: 'globalFunction')]
function attributedFunction(
    #[ParameterOnlyAttribute] string $param
): void {
    // Function implementation
}

// Interface with attributes
#[SimpleAttribute]
#[ConfigurableAttribute(name: 'TestInterface')]
interface AttributedInterface
{
    #[MethodOnlyAttribute]
    #[ConfigurableAttribute(name: 'interfaceMethod')]
    public function interfaceMethod(): void;
}

// Trait with attributes
#[SimpleAttribute]
trait AttributedTrait
{
    #[PropertyOnlyAttribute]
    public string $traitProperty;
    
    #[MethodOnlyAttribute]
    public function traitMethod(): void {}
}

// Enum with attributes (PHP 8.1+)
#[SimpleAttribute]
#[ConfigurableAttribute(name: 'Status')]
enum Status: string
{
    #[ConstantOnlyAttribute]
    #[ConfigurableAttribute(name: 'active_status')]
    case ACTIVE = 'active';
    
    #[ConstantOnlyAttribute]
    #[ConfigurableAttribute(name: 'inactive_status')]
    case INACTIVE = 'inactive';
    
    #[MethodOnlyAttribute]
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}

// Complex attribute with nested data
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RouteAttribute
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public array $middleware = [],
        public ?string $name = null,
        public array $requirements = []
    ) {}
}

// Controller-like class with routing attributes
#[RouteAttribute(path: '/api', middleware: ['auth', 'api'])]
class ApiController
{
    #[RouteAttribute(
        path: '/users',
        methods: ['GET'],
        name: 'users.index'
    )]
    public function index(): array
    {
        return [];
    }
    
    #[RouteAttribute(
        path: '/users/{id}',
        methods: ['GET'],
        name: 'users.show',
        requirements: ['id' => '\d+']
    )]
    public function show(int $id): array
    {
        return ['id' => $id];
    }
    
    #[RouteAttribute(
        path: '/users',
        methods: ['POST'],
        name: 'users.store',
        middleware: ['auth', 'api', 'throttle:60,1']
    )]
    public function store(array $data): array
    {
        return $data;
    }
}

// Attribute for validation
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validate
{
    public function __construct(
        public string $rule,
        public mixed $value = null,
        public ?string $message = null
    ) {}
}

// Model-like class with validation attributes
class UserModel
{
    #[Validate(rule: 'required')]
    #[Validate(rule: 'int')]
    #[Validate(rule: 'min', value: 1)]
    public int $id;
    
    #[Validate(rule: 'required')]
    #[Validate(rule: 'string')]
    #[Validate(rule: 'email')]
    #[Validate(rule: 'max', value: 255, message: 'Email too long')]
    public string $email;
    
    #[Validate(rule: 'required')]
    #[Validate(rule: 'string')]
    #[Validate(rule: 'min', value: 8, message: 'Password too short')]
    public string $password;
    
    #[Validate(rule: 'nullable')]
    #[Validate(rule: 'string')]
    #[Validate(rule: 'regex', value: '/^[0-9]{10}$/', message: 'Invalid phone format')]
    public ?string $phone = null;
}

// Attribute for dependency injection
#[Attribute(Attribute::TARGET_PARAMETER)]
class Inject
{
    public function __construct(public ?string $service = null) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Autowire
{
    public function __construct(public ?string $service = null) {}
}

// Service class with DI attributes
class ServiceWithDI
{
    #[Autowire]
    private LoggerInterface $logger;
    
    #[Autowire(service: 'app.mailer')]
    private MailerInterface $mailer;
    
    public function __construct(
        #[Inject] DatabaseInterface $database,
        #[Inject(service: 'app.cache')] CacheInterface $cache
    ) {
        // Constructor implementation
    }
}

// Interfaces for DI example
interface LoggerInterface {}
interface MailerInterface {}
interface DatabaseInterface {}
interface CacheInterface {}

// Anonymous class with attributes
$anonymousWithAttributes = new #[SimpleAttribute] class {
    #[PropertyOnlyAttribute]
    public string $property = 'value';
    
    #[MethodOnlyAttribute]
    public function method(): void {}
};