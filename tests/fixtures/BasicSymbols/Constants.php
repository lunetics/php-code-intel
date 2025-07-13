<?php

namespace TestFixtures\BasicSymbols;

// 1. Global constants using define()
define('GLOBAL_STRING', 'Hello World');
define('GLOBAL_NUMBER', 42);
define('GLOBAL_FLOAT', 3.14159);
define('GLOBAL_BOOL', true);
define('GLOBAL_NULL', null);
define('GLOBAL_ARRAY', ['a', 'b', 'c']); // PHP 7.0+

// Dynamic constant name
$constantName = 'DYNAMIC_CONSTANT';
define($constantName, 'Dynamic Value');

// Case-insensitive constant (deprecated in PHP 7.3+, removed in PHP 8.0)
// define('CASE_INSENSITIVE', 'value', true);

// 2. Namespace constants using const
const NAMESPACE_STRING = 'Namespace Constant';
const NAMESPACE_INT = 100;
const NAMESPACE_ARRAY = [1, 2, 3];
const NAMESPACE_EXPRESSION = NAMESPACE_INT * 2; // Can use expressions

// 3. Class constants with visibility modifiers
class MyClass
{
    // Public constants (default visibility)
    const PUBLIC_CONST = 'public';
    public const EXPLICIT_PUBLIC = 'explicitly public';
    
    // Private constants (PHP 7.1+)
    private const PRIVATE_CONST = 'private';
    
    // Protected constants (PHP 7.1+)
    protected const PROTECTED_CONST = 'protected';
    
    // Array constant
    public const CONFIG = [
        'host' => 'localhost',
        'port' => 3306
    ];
    
    // Constant referencing another constant
    public const DERIVED = self::PUBLIC_CONST . '_derived';
    
    public function demonstrateAccess(): void
    {
        // Accessing from within the class
        echo self::PUBLIC_CONST . PHP_EOL;
        echo self::PRIVATE_CONST . PHP_EOL;
        echo self::PROTECTED_CONST . PHP_EOL;
        echo static::PUBLIC_CONST . PHP_EOL; // Late static binding
    }
}

// Class extending MyClass
class ChildClass extends MyClass
{
    // Overriding public constant
    public const PUBLIC_CONST = 'overridden';
    
    // Cannot override private constants (they're not inherited)
    // private const PRIVATE_CONST = 'cannot override'; // This would be a new constant
    
    public function accessParentConstants(): void
    {
        echo self::PUBLIC_CONST . PHP_EOL;      // 'overridden'
        echo parent::PUBLIC_CONST . PHP_EOL;    // 'public'
        echo self::PROTECTED_CONST . PHP_EOL;   // Inherited
        // echo self::PRIVATE_CONST;            // Error: not accessible
    }
}

// 4. Interface constants
interface DatabaseInterface
{
    // Interface constants are always public
    const HOST = 'localhost';
    const PORT = 3306;
    const TIMEOUT = 30;
    
    // Can reference other interface constants
    const CONNECTION_STRING = self::HOST . ':' . self::PORT;
}

interface ExtendedDatabaseInterface extends DatabaseInterface
{
    // Can add new constants
    const MAX_CONNECTIONS = 100;
    
    // Cannot override parent interface constants
    // const HOST = 'changed'; // Fatal error
}

class DatabaseConnection implements DatabaseInterface
{
    public function getConnectionInfo(): array
    {
        return [
            'host' => self::HOST,
            'port' => self::PORT,
            'timeout' => self::TIMEOUT,
            'connection' => self::CONNECTION_STRING
        ];
    }
}

// 5. Enum cases (PHP 8.1+)
enum Status
{
    case PENDING;
    case ACTIVE;
    case INACTIVE;
    case DELETED;
}

// Backed enum with string values
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
    
    // Enums can have constants too
    public const DEFAULT_ROLE = self::GUEST;
    
    // Enums can have methods
    public function hasPermission(string $permission): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::USER => in_array($permission, ['read', 'write']),
            self::GUEST => $permission === 'read'
        };
    }
}

// Backed enum with integer values
enum Priority: int
{
    case LOW = 1;
    case MEDIUM = 5;
    case HIGH = 10;
    case CRITICAL = 20;
}

// 6. Usage examples demonstrating constant access
class ConstantUsageExamples
{
    public static function demonstrateAccess(): void
    {
        // Accessing global constants
        echo GLOBAL_STRING . PHP_EOL;
        echo GLOBAL_NUMBER . PHP_EOL;
        echo DYNAMIC_CONSTANT . PHP_EOL;
        
        // Accessing namespace constants
        echo NAMESPACE_STRING . PHP_EOL;
        echo \TestFixtures\BasicSymbols\NAMESPACE_INT . PHP_EOL;
        
        // Accessing class constants
        echo MyClass::PUBLIC_CONST . PHP_EOL;
        echo MyClass::CONFIG['host'] . PHP_EOL;
        // echo MyClass::PRIVATE_CONST; // Error: Cannot access private const
        
        // Accessing interface constants
        echo DatabaseInterface::HOST . PHP_EOL;
        echo DatabaseConnection::PORT . PHP_EOL; // Via implementing class
        
        // Accessing enum cases
        $status = Status::ACTIVE;
        $role = UserRole::ADMIN;
        echo $role->value . PHP_EOL; // 'admin'
        
        // Dynamic constant access
        echo constant('GLOBAL_STRING') . PHP_EOL;
        echo constant(MyClass::class . '::PUBLIC_CONST') . PHP_EOL;
        
        // Checking if constants are defined
        if (defined('GLOBAL_STRING')) {
            echo "GLOBAL_STRING is defined\n";
        }
        
        if (defined(MyClass::class . '::PUBLIC_CONST')) {
            echo "MyClass::PUBLIC_CONST is defined\n";
        }
        
        // Enum usage examples
        $priority = Priority::HIGH;
        echo "Priority value: " . $priority->value . PHP_EOL; // 10
        
        $userRole = UserRole::USER;
        if ($userRole->hasPermission('write')) {
            echo "User can write\n";
        }
        
        // Getting all enum cases
        $allStatuses = Status::cases();
        foreach ($allStatuses as $status) {
            echo $status->name . PHP_EOL;
        }
        
        // Magic constants examples
        echo __LINE__ . PHP_EOL;        // Current line number
        echo __FILE__ . PHP_EOL;        // Current file path
        echo __DIR__ . PHP_EOL;         // Current directory
        echo __NAMESPACE__ . PHP_EOL;   // Current namespace
        echo __CLASS__ . PHP_EOL;       // Current class name
        echo __METHOD__ . PHP_EOL;      // Current method name
        echo __FUNCTION__ . PHP_EOL;    // Current function name
    }
}

// Additional examples of constant usage patterns
function demonstrateConstantPatterns(): void
{
    // Using constants in switch/match statements
    $role = UserRole::ADMIN;
    
    switch ($role) {
        case UserRole::ADMIN:
            echo "Administrator access\n";
            break;
        case UserRole::USER:
            echo "Regular user access\n";
            break;
        case UserRole::GUEST:
            echo "Guest access\n";
            break;
    }
    
    // PHP 8.0+ match expression with enums
    $message = match($role) {
        UserRole::ADMIN => 'Full access granted',
        UserRole::USER => 'Limited access',
        UserRole::GUEST => 'Read-only access'
    };
    
    echo $message . PHP_EOL;
    
    // Using constants in array keys
    $config = [
        DatabaseInterface::HOST => '192.168.1.1',
        DatabaseInterface::PORT => 3307,
        'timeout' => DatabaseInterface::TIMEOUT
    ];
    
    // Iterating over enum cases
    foreach (Priority::cases() as $priority) {
        echo sprintf("Priority: %s = %d\n", $priority->name, $priority->value);
    }
}

// Example of using constants in type declarations (PHP 8.3+)
// class TypedConstants
// {
//     public function processStatus(Status $status): void
//     {
//         // Type-safe enum parameter
//     }
// }