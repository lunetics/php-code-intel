<?php
/**
 * Namespace Declarations Test Fixture
 * 
 * This file demonstrates various namespace declaration patterns in PHP,
 * including multiple namespaces in one file, bracketed syntax, and global namespace.
 */

// Simple namespace declaration
namespace App\Controllers;

class HomeController {
    public function index() {
        return "Home page";
    }
}

// Another namespace in the same file
namespace App\Models;

class User {
    public $name;
    public $email;
}

// Bracketed namespace syntax (recommended for multiple namespaces)
namespace App\Services {
    class AuthService {
        public function authenticate($user, $password) {
            return true;
        }
    }
    
    interface ServiceInterface {
        public function execute();
    }
}

// Another bracketed namespace
namespace App\Repositories {
    use App\Models\User;
    
    class UserRepository {
        public function find($id) {
            return new User();
        }
    }
}

// Global namespace fallback (no namespace)
namespace {
    // This is in the global namespace
    function globalFunction() {
        return "I'm in the global namespace";
    }
    
    class GlobalClass {
        public $value = "global";
    }
    
    const GLOBAL_CONSTANT = 42;
}

// Deeply nested namespace
namespace App\Http\Controllers\Api\V1 {
    class UserController {
        public function list() {
            return [];
        }
    }
}

// Namespace with numbers (valid in PHP)
namespace App\Api\V2 {
    class EndpointController {
        public function handle() {}
    }
}

// Unicode namespace (valid but not recommended)
namespace App\Ψ\Unicode {
    class SpecialClass {
        public function λ() {
            return "Unicode method";
        }
    }
}

// Sub-namespace declaration
namespace App\Utilities\Helpers {
    function formatDate($date) {
        return date('Y-m-d', strtotime($date));
    }
    
    const DATE_FORMAT = 'Y-m-d H:i:s';
}

// Empty namespace block (valid but unusual)
namespace App\Empty {
    // No content
}

// Namespace with reserved keywords as parts (valid)
namespace App\Public\Protected {
    class KeywordNamespace {
        public function test() {
            return "Keywords in namespace path";
        }
    }
}