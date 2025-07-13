<?php
/**
 * Use Statements Test Fixture
 * 
 * This file demonstrates all types of use statements in PHP,
 * including simple imports, aliases, function/constant imports, and group declarations.
 */

namespace App\Testing\UseStatements;

// Simple use statements
use App\Models\User;
use App\Controllers\HomeController;
use App\Services\AuthService;

// Aliased imports with 'as'
use App\Models\User as UserModel;
use App\Controllers\HomeController as Home;
use App\Services\AuthService as Auth;
use App\Repositories\UserRepository as UserRepo;

// Multiple classes from same namespace
use App\Http\Request;
use App\Http\Response;
use App\Http\JsonResponse;

// Function imports (PHP 5.6+)
use function App\Utilities\Helpers\formatDate;
use function App\Utilities\Helpers\sanitizeInput;
use function strlen;  // Built-in function
use function array_map;

// Function imports with aliases
use function App\Utilities\Helpers\formatDate as format;
use function App\Utilities\Helpers\sanitizeInput as clean;

// Constant imports (PHP 5.6+)
use const App\Utilities\Helpers\DATE_FORMAT;
use const App\Utilities\Helpers\MAX_LENGTH;
use const PHP_EOL;  // Built-in constant
use const E_ALL;

// Constant imports with aliases
use const App\Utilities\Helpers\DATE_FORMAT as FORMAT;
use const App\Utilities\Helpers\MAX_LENGTH as MAX;

// Group use declarations (PHP 7.0+)
use App\Models\{User as U, Post, Comment};
use App\Controllers\{
    HomeController as HC,
    UserController,
    AdminController
};

// Group use with common prefix
use App\Services\{
    AuthService,
    EmailService,
    LoggingService,
    CacheService
};

// Mixed group imports (classes, functions, and constants in one group)
use App\Utilities\{
    Helper,
    function formatDate as formatD,
    function parseUrl,
    const DATE_FORMAT as DF,
    const TIME_FORMAT
};

// Nested group use declarations
use App\Http\{
    Request,
    Response,
    Middleware\{
        AuthMiddleware,
        CorsMiddleware,
        ThrottleMiddleware
    },
    Controllers\{
        ApiController,
        WebController
    }
};

// Complex mixed group with different types
use App\Domain\{
    User\UserEntity,
    User\UserRepository as Repo,
    function User\validateEmail,
    function User\hashPassword,
    const User\MIN_PASSWORD_LENGTH,
    const User\MAX_USERNAME_LENGTH,
    Post\{
        PostEntity,
        PostRepository,
        function createSlug,
        const MAX_TITLE_LENGTH
    }
};

// Group use with trailing comma (PHP 7.2+)
use App\Traits\{
    HasTimestamps,
    SoftDeletes,
    Searchable,
};

// Importing from sub-namespaces
use App\Exceptions\Http\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Exceptions\Auth\UnauthorizedException as Unauthorized;

// Multiple use statements on one line (valid but not recommended)
use App\A; use App\B; use App\C;

// Use statements for interfaces and traits
use App\Contracts\ServiceInterface;
use App\Contracts\RepositoryInterface;
use App\Traits\Singleton;
use App\Traits\Observable;

// Global namespace imports (with leading backslash)
use \DateTime;
use \Exception;
use \stdClass;
use \ArrayObject as AO;

// Importing from vendor namespaces
use Vendor\Package\Class1;
use Vendor\Package\Subpackage\Class2 as VendorClass;

class UseStatementExample {
    public function demonstrateUsage() {
        // Using imported classes
        $user = new User();  // Simple import
        $userModel = new UserModel();  // Aliased import
        $home = new Home();  // Aliased controller
        
        // Using imported functions
        $date = formatDate('2023-01-01');  // Imported function
        $cleaned = clean('<script>alert("xss")</script>');  // Aliased function
        
        // Using imported constants
        echo DATE_FORMAT;  // Imported constant
        echo FORMAT;  // Aliased constant
        
        // Using group imported items
        $u = new U();  // Group imported with alias
        $post = new Post();  // Group imported
        
        // Using nested group imports
        $request = new Request();
        $auth = new AuthMiddleware();
        
        return true;
    }
}