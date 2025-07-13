<?php

declare(strict_types=1);

namespace CodeIntel\Finder;

/**
 * Calculates confidence levels for symbol usage
 * 
 * Confidence Levels:
 * - CERTAIN: Direct, unambiguous usage (new Class(), Class::method())
 * - PROBABLE: Type-hinted or contextual usage (?->, method chaining)
 * - POSSIBLE: Dynamic but traceable (new $class, $obj->$method)
 * - DYNAMIC: Highly dynamic or magic usage (call_user_func, __call)
 */
class ConfidenceScorer
{
    public const CERTAIN = 'CERTAIN';
    public const PROBABLE = 'PROBABLE';
    public const POSSIBLE = 'POSSIBLE';
    public const DYNAMIC = 'DYNAMIC';
    
    /**
     * Score the confidence level of a code usage
     */
    public function score(string $code): string
    {
        $code = trim($code);
        
        // DYNAMIC - Magic methods and highly dynamic invocation (check first)
        if (str_contains($code, 'call_user_func') || 
            preg_match('/(?<!new\s)(?<!->)\$[a-zA-Z_]\w*\s*\(/', $code) ||  // $obj() invoke pattern (not new $var() or $obj->$method())
            preg_match('/\$\{[^}]+\}/', $code) ||                           // complex variable references like ${$varName}
            str_contains($code, '__call') ||
            str_contains($code, '->invoke')) {
            return self::DYNAMIC;
        }
        
        // POSSIBLE - Variable-based method chaining (check before CERTAIN)
        if (preg_match('/\$(?!this)\w+->\w+\(\)->\w+\(\)/', $code)) { // variable method chaining (not $this)
            return self::POSSIBLE;
        }
        
        // PROBABLE - Nullsafe operator and method chaining patterns (check before CERTAIN)
        if (str_contains($code, '?->') ||
            preg_match('/\$this->\w+\(\)->\w+\(\)/', $code) ||      // $this method chaining like $this->getService()->process()
            preg_match('/app\([^)]*\)->\w+\(\)/', $code)) {         // Laravel app() helper chaining like app()->method()
            return self::PROBABLE;
        }
        
        // CERTAIN - Direct, unambiguous usage
        if (preg_match('/new\s+[A-Z]\w*\s*\(/', $code) ||           // new ClassName()
            preg_match('/[A-Z]\w*::[a-zA-Z_]\w*\s*\(/', $code) ||   // ClassName::method()
            preg_match('/[A-Z]\w*::[A-Z_][A-Z0-9_]*/', $code) ||    // ClassName::CONSTANT
            preg_match('/function\s+\w+\s*\([^)]*[A-Z]\w+\s+\$/', $code) || // function(Type $param)
            preg_match('/\$\w+->\w+\s*\(/', $code) ||               // $obj->method()
            str_contains($code, 'instanceof') ||
            str_contains($code, '::class') ||
            str_contains($code, 'self::') ||
            str_contains($code, 'parent::') ||
            str_contains($code, 'static::')) {
            return self::CERTAIN;
        }
        
        // POSSIBLE - Dynamic but traceable  
        if (preg_match('/new\s+\$\w+/', $code) ||                   // new $className
            preg_match('/\$\w+->\$\w+\s*\(/', $code) ||            // $obj->$method()
            preg_match('/\$\w+\s*=\s*["\'][^"\']+["\']/', $code) || // $class = "ClassName"
            str_contains($code, 'class_exists') ||
            str_contains($code, 'is_a') ||
            preg_match('/\[\s*\$\w+\s*,\s*["\'][^"\']+["\']\s*\]/', $code)) { // [$obj, "method"]
            return self::POSSIBLE;
        }
        
        return self::POSSIBLE;
    }
    
    /**
     * Score with additional context information
     */
    public function scoreWithContext(string $code, string $context): string
    {
        // Check for typed context that increases confidence
        if (preg_match('/function\s+\w+\s*\([^)]*[A-Z]\w+\s+\$\w+/', $context) ||  // function(Type $param)
            str_contains($context, '@var') ||                                        // @var Type
            str_contains($context, 'private') && str_contains($context, '$') ||     // private Type $prop
            str_contains($code, '?->') ||                                           // nullsafe operator
            str_contains($code, '->getService()->')) {                              // method chaining
            return self::PROBABLE;
        }
        
        return $this->score($code);
    }
}