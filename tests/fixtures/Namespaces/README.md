# PHP Namespace Test Fixtures - Comprehensive Analysis

This directory contains comprehensive test fixtures for PHP namespace features, demonstrating various declaration syntaxes, import mechanisms, and name resolution scenarios.

## Files Overview

### 1. NamespaceDeclarations.php
Demonstrates various namespace declaration syntaxes and patterns.

**Key Features:**
- **Simple namespace declarations**: Basic `namespace Name;` syntax
- **Nested namespaces**: Multi-level namespaces like `Parent\Child\GrandChild`
- **Bracketed syntax**: Recommended for multiple namespaces in one file
- **Global namespace**: Using `namespace { }` for global scope
- **Unicode namespaces**: Valid Unicode characters in namespace names
- **Numeric namespaces**: Numbers in namespace names (e.g., `Namespace123`)
- **Very long namespaces**: Deep nesting demonstration

**Important Patterns:**
```php
// Simple declaration
namespace SimpleNamespace;

// Bracketed syntax (recommended for multiple namespaces)
namespace BracketedNamespace {
    // code here
}

// Global namespace fallback
namespace {
    // global code here
}
```

### 2. UseStatements.php
Comprehensive demonstration of PHP's use statement capabilities.

**Key Features:**

#### Basic Imports
- Simple use statements: `use DateTime;`
- Multiple imports from same namespace
- Importing from deeply nested namespaces

#### Aliased Imports
- Using `as` keyword: `use Very\Long\Name as ShortName;`
- Disambiguation with aliases: Multiple `Logger` classes with different aliases
- Strategic aliasing for readability

#### Function Imports (PHP 5.6+)
- Direct function imports: `use function strlen;`
- Aliased function imports: `use function str_replace as strReplace;`
- Namespace function imports

#### Constant Imports (PHP 5.6+)
- Direct constant imports: `use const PHP_VERSION;`
- Aliased constant imports: `use const E_ERROR as ERROR_LEVEL;`
- Both global and namespaced constants

#### Group Use Declarations (PHP 7.0+)
- Simple grouping: `use Namespace\{ClassA, ClassB, ClassC};`
- Nested grouping with common prefixes
- Mixed imports (classes, functions, constants) in groups
- Complex nested structures

**Advanced Patterns:**
```php
// Mixed group import
use Framework\{
    Core\Application,
    function Core\handleRequest,
    const Core\VERSION
};

// Nested group use with sub-grouping
use Vendor\Library\{
    Client\Contracts\{
        ClientInterface,
        ResponseInterface
    },
    Middleware\{
        AuthMiddleware,
        LoggingMiddleware
    }
};
```

### 3. NameResolution.php
Detailed exploration of PHP's name resolution rules.

**Key Concepts:**

#### 1. Fully Qualified Names (FQN)
- Start with `\`
- Always resolve from global namespace
- Example: `\DateTime`, `\Namespace\Class`

#### 2. Relative Names
- Don't start with `\`
- Resolved relative to current namespace or imports
- Example: `DateTime` (uses import), `LocalClass` (current namespace)

#### 3. Namespace-Relative Names
- Contain `\` but don't start with `\`
- Resolved relative to current namespace
- Example: `SubNamespace\Class`

#### 4. Global Fallback Rules
- **Classes**: NO fallback (must import or use FQN)
- **Functions**: YES fallback (falls back to global if not found)
- **Constants**: YES fallback (falls back to global if not found)

**Resolution Examples:**
```php
namespace MyNamespace;
use DateTime;

// Classes
new DateTime();        // Uses import (resolves to \DateTime)
new ArrayObject();     // ERROR - no import, no fallback
new \ArrayObject();    // OK - fully qualified

// Functions
strlen('test');        // OK - falls back to global \strlen
time();               // OK - falls back to global \time

// Constants
PHP_VERSION;          // OK - falls back to global constant
TRUE;                 // OK - falls back to global constant
```

## Name Resolution Algorithm

PHP follows these steps when resolving a name:

1. **For Fully Qualified Names** (starting with `\`):
   - Use exactly as specified
   - No further resolution needed

2. **For Unqualified Names** (no `\`):
   - Check use statements for exact match
   - For classes: Check current namespace
   - For functions/constants: Check current namespace, then fall back to global

3. **For Qualified Names** (contains `\` but doesn't start with `\`):
   - Check use statements for matching prefix
   - Prepend current namespace

## Edge Cases and Special Scenarios

### Case Sensitivity
- Namespaces are case-insensitive: `\DateTime` = `\DATETIME` = `\DaTeTiMe`
- Class names may be case-sensitive depending on filesystem

### Special Contexts
- `::class` constant resolves names at compile time
- String class names are not resolved until runtime
- `instanceof` and `catch` blocks follow normal resolution rules

### Dynamic Resolution
```php
$className = DateTime::class;     // Resolved to \DateTime
$instance = new $className();     // Works with resolved name

$string = 'DateTime';            // Just a string
$instance = new $string();       // Works only if DateTime exists in global
```

### Keywords in Namespaces
- Some keywords allowed: `default`, `abstract`
- Some keywords forbidden: `class`, `interface`

## Best Practices Demonstrated

1. **Use Bracketed Syntax** for multiple namespaces in one file
2. **Group Related Imports** using group use syntax
3. **Use Aliases** for disambiguation and readability
4. **Be Explicit** with global classes in namespaced code
5. **Understand Fallback Rules** to avoid confusion

## Testing Considerations

These fixtures are designed to test:
- Namespace declaration parsing
- Use statement resolution
- Symbol table construction
- Name resolution accuracy
- Edge case handling
- Import precedence rules
- Scope boundary detection

Each file progressively builds complexity, making it easy to test individual features or complete namespace resolution systems.