# PHP Symbol Types Reference

## Overview

This document provides a comprehensive reference for all PHP symbol types that the Code Intelligence Tool must handle, including how to identify them in the AST and confidence levels for each type.

## Symbol Categories

### 1. Class-like Symbols

#### Classes
```php
// Standard class
class UserService {}

// Final class
final class ImmutableService {}

// Abstract class
abstract class BaseController {}

// Anonymous class
$logger = new class implements LoggerInterface {};
```

**AST Node**: `PhpParser\Node\Stmt\Class_`
**Properties**:
- `name`: Class name (null for anonymous)
- `extends`: Parent class
- `implements`: Implemented interfaces
- `flags`: Abstract, final modifiers

**Usage Patterns**:
- `new ClassName()` - CERTAIN
- `ClassName::method()` - CERTAIN
- `instanceof ClassName` - CERTAIN
- `function(ClassName $param)` - CERTAIN
- `$class = 'ClassName'; new $class()` - POSSIBLE

#### Interfaces
```php
interface PaymentGateway {
    public function process(Payment $payment): Result;
}
```

**AST Node**: `PhpParser\Node\Stmt\Interface_`
**Usage Patterns**:
- `implements Interface` - CERTAIN
- `instanceof Interface` - CERTAIN
- Type declarations - CERTAIN

#### Traits
```php
trait Timestampable {
    protected $createdAt;
    public function touch() {}
}

class Post {
    use Timestampable;
}
```

**AST Node**: `PhpParser\Node\Stmt\Trait_`
**Usage Patterns**:
- `use TraitName` - CERTAIN
- `use TraitName { method as alias; }` - CERTAIN
- Method calls on using class - PROBABLE

#### Enums (PHP 8.1+)
```php
enum Status {
    case PENDING;
    case APPROVED;
    case REJECTED;
}

enum HttpStatus: int {
    case OK = 200;
    case NOT_FOUND = 404;
}
```

**AST Node**: `PhpParser\Node\Stmt\Enum_`
**Usage Patterns**:
- `Status::PENDING` - CERTAIN
- `function(Status $status)` - CERTAIN
- Match expressions - CERTAIN

### 2. Function Symbols

#### Global Functions
```php
function processData(array $data): array {
    return array_map('trim', $data);
}
```

**AST Node**: `PhpParser\Node\Stmt\Function_`
**Usage Patterns**:
- `functionName()` - CERTAIN
- `'functionName'` in callbacks - POSSIBLE
- `call_user_func('functionName')` - POSSIBLE

#### Namespaced Functions
```php
namespace App\Helpers;

function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

// Usage
\App\Helpers\formatMoney(99.99);
use function App\Helpers\formatMoney;
formatMoney(99.99);
```

**AST Node**: `PhpParser\Node\Stmt\Function_` with namespace context
**Resolution**: Must resolve based on current namespace and imports

#### Closures
```php
$multiplier = 2;
$double = function($x) use ($multiplier) {
    return $x * $multiplier;
};

// Arrow function (PHP 7.4+)
$triple = fn($x) => $x * 3;
```

**AST Node**: 
- `PhpParser\Node\Expr\Closure`
- `PhpParser\Node\Expr\ArrowFunction`

**Note**: Anonymous functions are typically not searchable by name

### 3. Method Symbols

#### Instance Methods
```php
class Service {
    public function process() {}
    protected function validate() {}
    private function log() {}
}
```

**AST Node**: `PhpParser\Node\Stmt\ClassMethod`
**Properties**:
- `flags`: Public/protected/private, static, abstract, final
- `name`: Method name
- `params`: Parameters
- `returnType`: Return type declaration

**Usage Patterns**:
- `$obj->method()` - CERTAIN (if $obj type known)
- `$this->method()` - CERTAIN (within class)
- `parent::method()` - CERTAIN
- `$obj->$method()` - POSSIBLE

#### Static Methods
```php
class Factory {
    public static function create(): self {
        return new self();
    }
}

Factory::create();
```

**Usage Patterns**:
- `ClassName::method()` - CERTAIN
- `self::method()` - CERTAIN
- `static::method()` - CERTAIN (late static binding)
- `parent::method()` - CERTAIN

#### Magic Methods
```php
class Magic {
    public function __construct() {}
    public function __destruct() {}
    public function __call($name, $arguments) {}
    public static function __callStatic($name, $arguments) {}
    public function __get($name) {}
    public function __set($name, $value) {}
    public function __isset($name) {}
    public function __unset($name) {}
    public function __toString() {}
    public function __invoke() {}
    public function __clone() {}
}
```

**Special Handling**:
- `__call`: Makes any undefined method DYNAMIC confidence
- `__get/__set`: Makes property access DYNAMIC
- `__invoke`: Makes object callable

### 4. Property Symbols

#### Class Properties
```php
class Entity {
    public string $name;
    protected ?int $id = null;
    private array $data = [];
    
    public static string $table = 'entities';
}
```

**AST Node**: `PhpParser\Node\Stmt\Property`
**Modifiers**: Public/protected/private, static, readonly (PHP 8.1+)

#### Promoted Properties (PHP 8.0+)
```php
class User {
    public function __construct(
        public string $name,
        private int $age,
        protected ?string $email = null
    ) {}
}
```

**AST Node**: Constructor parameters with visibility modifiers

### 5. Constant Symbols

#### Class Constants
```php
class Config {
    public const VERSION = '1.0.0';
    private const SECRET = 'hidden';
    
    // Typed constants (PHP 8.3+)
    public const string APP_NAME = 'MyApp';
}
```

**AST Node**: `PhpParser\Node\Stmt\ClassConst`
**Usage**: `Config::VERSION` - CERTAIN

#### Global Constants
```php
// Define syntax
define('APP_PATH', __DIR__);
define('MAX_SIZE', 1024 * 1024);

// Const syntax
const PI = 3.14159;
const DEBUG = true;
```

**AST Node**: 
- `PhpParser\Node\Expr\FuncCall` (for define)
- `PhpParser\Node\Stmt\Const_`

#### Namespace Constants
```php
namespace App\Config;

const DEFAULT_TIMEOUT = 30;
const MAX_RETRIES = 3;
```

### 6. Variable Symbols

#### Type-Declared Variables
```php
/** @var UserService $service */
$service = $container->get(UserService::class);

// Typed properties provide type info
$user->name; // If name is typed string

// Parameter types
function process(User $user, ?string $note = null) {
    // $user is certainly User type
}
```

**Confidence Based on Declaration**:
- Typed parameter: CERTAIN within function
- Typed property: CERTAIN when accessed
- PHPDoc @var: PROBABLE
- No type info: Track assignments

### 7. Special Constructs

#### Use Statements (Imports)
```php
use App\Service\UserService;
use App\Entity\{User, Post, Comment};
use function App\Helpers\formatDate;
use const App\Config\MAX_SIZE;

// Aliases
use Symfony\Component\HttpFoundation\Request as HttpRequest;
```

**AST Node**: `PhpParser\Node\Stmt\Use_`
**Important**: These affect symbol resolution

#### Namespace Declarations
```php
namespace App\Controllers;

// Bracketed syntax
namespace App\Models {
    class User {}
}
```

**AST Node**: `PhpParser\Node\Stmt\Namespace_`

## Confidence Level Matrix

| Symbol Type | Usage Pattern | Confidence |
|-------------|---------------|------------|
| Class | `new Class()` | CERTAIN |
| Class | `Class::staticMethod()` | CERTAIN |
| Class | `instanceof Class` | CERTAIN |
| Class | Type declaration | CERTAIN |
| Class | `$class = 'Class'; new $class()` | POSSIBLE |
| Method | `$obj->method()` with known type | CERTAIN |
| Method | `$obj->method()` with @var hint | PROBABLE |
| Method | `$obj->$method()` | POSSIBLE |
| Method | `$obj->undefinedMethod()` with __call | DYNAMIC |
| Property | `$obj->property` with typed property | CERTAIN |
| Property | `$obj->property` with @property | PROBABLE |
| Property | `$obj->$prop` | POSSIBLE |
| Property | `$obj->undefined` with __get | DYNAMIC |
| Function | `functionName()` | CERTAIN |
| Function | `'functionName'` in callback | POSSIBLE |
| Function | `call_user_func($func)` | DYNAMIC |
| Constant | `Class::CONST` | CERTAIN |
| Constant | `constant('CONST')` | POSSIBLE |

## AST Visitor Implementation Notes

### Symbol Extraction Visitor

```php
class SymbolExtractorVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        switch (true) {
            case $node instanceof Stmt\Class_:
                $this->extractClass($node);
                break;
            case $node instanceof Stmt\Interface_:
                $this->extractInterface($node);
                break;
            case $node instanceof Stmt\Trait_:
                $this->extractTrait($node);
                break;
            case $node instanceof Stmt\Function_:
                $this->extractFunction($node);
                break;
            case $node instanceof Stmt\ClassMethod:
                $this->extractMethod($node);
                break;
            case $node instanceof Stmt\Property:
                $this->extractProperty($node);
                break;
            // ... etc
        }
    }
}
```

### Usage Detection Visitor

```php
class UsageDetectorVisitor extends NodeVisitorAbstract
{
    private string $targetSymbol;
    private array $usages = [];
    
    public function enterNode(Node $node)
    {
        switch (true) {
            case $node instanceof Expr\New_:
                $this->checkNewInstance($node);
                break;
            case $node instanceof Expr\StaticCall:
                $this->checkStaticCall($node);
                break;
            case $node instanceof Expr\MethodCall:
                $this->checkMethodCall($node);
                break;
            case $node instanceof Expr\Instanceof_:
                $this->checkInstanceof($node);
                break;
            // ... etc
        }
    }
}
```

## Special Considerations

### Dynamic Features
- Magic methods affect confidence levels
- String-based class/function names need special handling
- Reflection API usage indicates dynamic behavior

### Namespace Resolution
- Always resolve to fully qualified names
- Consider current namespace context
- Apply use statement mappings
- Handle relative namespace references

### PHP Version Differences
- Enums: PHP 8.1+
- Readonly properties: PHP 8.1+
- Constructor promotion: PHP 8.0+
- Arrow functions: PHP 7.4+
- Typed properties: PHP 7.4+

### Framework Patterns
- Laravel facades are static calls
- Symfony DI container get() returns dynamic types
- Doctrine entities have magic properties
- PHPUnit mocks are dynamic

---

*This reference covers all PHP symbol types. Each must be handled correctly for accurate usage finding and refactoring support.*