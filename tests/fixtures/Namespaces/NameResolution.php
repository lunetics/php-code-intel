<?php
/**
 * Name Resolution Test Fixture
 * 
 * This file demonstrates PHP's name resolution rules,
 * including fully qualified names, relative names, and global fallbacks.
 */

namespace App\Testing\Resolution;

use App\Models\User;
use App\Services\Logger;
use DateTime as DT;
use function strlen;
use const PHP_EOL;

class NameResolutionExample {
    
    /**
     * Fully Qualified Names (FQN)
     * Names that start with \ are always resolved from the global namespace
     */
    public function fullyQualifiedNames() {
        // FQN for global classes
        $date = new \DateTime();  // Global DateTime class
        $exception = new \Exception('Error');  // Global Exception
        $obj = new \stdClass();  // Global stdClass
        
        // FQN for namespaced classes
        $user = new \App\Models\User();  // Explicit full path
        $logger = new \App\Services\Logger();  // Ignores use statement
        
        // FQN in function calls
        $length = \strlen('hello');  // Global function
        $array = \array_map('trim', ['a ', ' b']);  // Global function
        
        // FQN for constants
        echo \PHP_VERSION;  // Global constant
        echo \E_ERROR;  // Global constant
        
        // FQN in static calls
        $value = \App\Helpers\StringHelper::format('test');
        
        // FQN in instanceof
        if ($date instanceof \DateTime) {
            echo "It's a DateTime";
        }
    }
    
    /**
     * Relative Names
     * Names without namespace separator that are resolved relative to current namespace
     */
    public function relativeNames() {
        // Relative to current namespace (App\Testing\Resolution)
        $resolver = new NameResolver();  // Looks for App\Testing\Resolution\NameResolver
        $helper = new SubNamespace\Helper();  // App\Testing\Resolution\SubNamespace\Helper
        
        // Using imported names (from use statements)
        $user = new User();  // Resolves to App\Models\User (imported)
        $logger = new Logger();  // Resolves to App\Services\Logger (imported)
        $date = new DT();  // Resolves to DateTime (imported with alias)
        
        // Relative in static calls
        Helper::doSomething();  // App\Testing\Resolution\Helper
        SubNamespace\Tool::process();  // App\Testing\Resolution\SubNamespace\Tool
    }
    
    /**
     * Namespace-Relative Names
     * Names containing namespace separator but not starting with \
     */
    public function namespaceRelativeNames() {
        // Relative to current namespace
        $entity = new Models\Entity();  // App\Testing\Resolution\Models\Entity
        $service = new Services\EmailService();  // App\Testing\Resolution\Services\EmailService
        
        // Multiple levels
        $controller = new Http\Controllers\ApiController();  // App\Testing\Resolution\Http\Controllers\ApiController
        
        // In static calls
        Helpers\ArrayHelper::flatten([]);  // App\Testing\Resolution\Helpers\ArrayHelper
        
        // In instanceof
        if ($entity instanceof Models\Entity) {
            echo "It's an entity";
        }
    }
    
    /**
     * Global Fallback Behavior
     * Functions and constants fall back to global namespace if not found in current namespace
     */
    public function globalFallback() {
        // Functions: PHP first looks in current namespace, then falls back to global
        
        // This will use the global strlen function (fallback)
        $length = strlen('hello');  // Falls back to \strlen
        
        // Unless there's a function in current namespace
        $custom = customFunction();  // Uses App\Testing\Resolution\customFunction if exists
        
        // Or it's imported
        $imported = strlen('test');  // Uses imported strlen (which is global)
        
        // Constants also fall back to global
        echo PHP_EOL;  // Falls back to \PHP_EOL
        echo DATE_FORMAT;  // Uses App\Testing\Resolution\DATE_FORMAT if exists, else error
        
        // Classes DO NOT fall back to global
        // $date = new DateTime();  // ERROR! Looks for App\Testing\Resolution\DateTime
        // Must use:
        $date = new \DateTime();  // Correct: FQN
        // Or import it:
        // use DateTime;
        // $date = new DateTime();  // Correct: imported
    }
    
    /**
     * Special Resolution Cases
     */
    public function specialCases() {
        // Keywords cannot be used as namespace parts directly
        // But can be used in FQN
        $obj = new \App\Public\Protected\KeywordNamespace();
        
        // Dynamic class names
        $className = 'DateTime';
        $date1 = new $className();  // ALWAYS resolved as FQN (\DateTime)
        
        $className = 'App\Models\User';
        $user1 = new $className();  // Resolved as \App\Models\User
        
        $className = User::class;  // Gets FQN string: "App\Models\User"
        $user2 = new $className();
        
        // namespace keyword
        $currentNs = __NAMESPACE__;  // "App\Testing\Resolution"
        $class = __NAMESPACE__ . '\Helper';  // "App\Testing\Resolution\Helper"
        
        // Class name resolution in strings
        $className = User::class;  // "App\Models\User" (resolved)
        $namespaceName = namespace\Helper::class;  // "App\Testing\Resolution\Helper"
        
        // Resolution in catch blocks
        try {
            throw new \Exception();
        } catch (\Exception $e) {  // FQN
            // Handle exception
        } catch (CustomException $e) {  // Relative (looks in current namespace first)
            // Handle custom exception
        }
        
        // Resolution in type hints
        function processUser(User $user) {}  // Uses imported User
        function processDate(\DateTime $date) {}  // FQN
        function processHelper(Helper $helper) {}  // Relative to current namespace
        
        // Resolution in return types
        function getUser(): User {}  // Uses imported User
        function getDate(): \DateTime {}  // FQN
        function getInstance(): self {}  // Special keyword
        
        // Case sensitivity
        // Namespaces, classes, and functions are case-insensitive
        $user = new \APP\MODELS\USER();  // Works (but not recommended)
        $date = new \datetime();  // Works
        
        // But it's best practice to match the case exactly
        $user = new \App\Models\User();  // Recommended
    }
    
    /**
     * Name Resolution in Different Contexts
     */
    public function contextualResolution() {
        // In namespace declaration - no resolution
        // namespace App\Models;  // Literal, no resolution
        
        // In use statements - partial resolution
        // use App\Models\User;  // No leading \, but treated as FQN
        
        // In class extension
        class MyUser extends User {}  // Uses imported User
        class MyDateTime extends \DateTime {}  // FQN
        
        // In interface implementation
        class MyService implements ServiceInterface {}  // Relative
        class MyIterator implements \Iterator {}  // FQN
        
        // In trait usage
        class MyClass {
            use \App\Traits\Singleton;  // FQN
            use Logger;  // Relative/imported
        }
        
        // In anonymous classes
        $obj = new class extends User {};  // Uses imported User
        $iter = new class implements \Iterator {  // FQN
            public function current() {}
            public function key() {}
            public function next() {}
            public function rewind() {}
            public function valid() {}
        };
    }
}

// Helper class in same namespace
class Helper {
    public static function doSomething() {
        return "Helper in same namespace";
    }
}

// Custom function in current namespace
function customFunction() {
    return "Custom function in App\Testing\Resolution";
}

// Custom constant in current namespace
const DATE_FORMAT = 'Y-m-d';

// Subnamespace
namespace App\Testing\Resolution\SubNamespace;

class Helper {
    public function assist() {
        return "SubNamespace Helper";
    }
}

class Tool {
    public static function process() {
        return "Processing...";
    }
}

// Another namespace to show resolution differences
namespace App\Testing\Resolution\Models;

class Entity {
    public $id;
    public $name;
}

// Custom exception in a different namespace
namespace App\Testing\Resolution;

class CustomException extends \Exception {
    // Custom exception implementation
}

class NameResolver {
    public function resolve($name) {
        return "Resolving: " . $name;
    }
}