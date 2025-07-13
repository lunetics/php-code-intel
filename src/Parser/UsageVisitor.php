<?php

declare(strict_types=1);

namespace CodeIntel\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * AST visitor that finds usages of symbols in PHP code
 */
class UsageVisitor extends NodeVisitorAbstract
{
    private array $usages = [];
    private string $currentNamespace = '';
    private array $useStatements = [];
    private string $targetSymbol;
    private string $filePath;
    private array $fileLines;
    private string $normalizedTarget;
    private ?string $targetClass = null;
    private ?string $targetMethod = null;

    public function __construct(string $targetSymbol, string $filePath, ?string $fileContent = null)
    {
        $this->targetSymbol = $targetSymbol;
        $this->filePath = $filePath;
        
        // Pre-compute normalized target for performance
        $this->normalizedTarget = ltrim($targetSymbol, '\\');
        
        // Pre-parse class::method if applicable
        if (strpos($targetSymbol, '::') !== false) {
            [$this->targetClass, $this->targetMethod] = explode('::', $targetSymbol, 2);
        }
        
        // If file content is provided, use it; otherwise read from file
        if ($fileContent !== null) {
            $this->fileLines = explode("\n", $fileContent);
        } else {
            $this->fileLines = file($filePath, FILE_IGNORE_NEW_LINES) ?: [];
        }
    }

    public function enterNode(Node $node): void
    {
        // Track namespace context
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : '';
            return;
        }

        // Track use statements
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $alias = $use->alias ? $use->alias->toString() : $use->name->getLast();
                $this->useStatements[$alias] = $use->name->toString();
            }
            return;
        }

        // Find symbol usages
        $this->checkForUsage($node);
    }

    private function checkForUsage(Node $node): void
    {
        $line = $node->getStartLine();
        $code = $this->getCodeAtLine($line);

        // Check for class instantiation
        if ($node instanceof Node\Expr\New_) {
            $className = $this->resolveClassName($node->class);
            if ($className && $this->matchesTarget($className)) {
                $this->addUsage($line, $code, 'CERTAIN', 'instantiation');
            }
        }

        // Check for static method calls
        if ($node instanceof Node\Expr\StaticCall) {
            $className = $this->resolveClassName($node->class);
            if ($className) {
                $methodName = $node->name instanceof Node\Identifier ? $node->name->name : null;
                $fullName = $className . '::' . $methodName;
                
                // Check if this matches our target directly
                if ($this->matchesTarget($fullName) || $this->matchesTarget($className)) {
                    $this->addUsage($line, $code, 'CERTAIN', 'static_call');
                }
                
                // Special handling for parent calls - they could match any parent class method
                if ($className === 'parent' && $methodName && $this->targetMethod === $methodName) {
                    $this->addUsage($line, $code, 'CERTAIN', 'parent_call');
                }
            }
        }

        // Check for method calls (both regular and nullsafe)
        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall) {
            $methodName = null;
            
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->name;
            } elseif ($node->name instanceof Node\Expr\Variable) {
                // Dynamic method call - we can't determine the exact method at parse time
                // but we can check if the target symbol method name might match
                if ($this->targetMethod !== null) {
                    // For dynamic calls, we add it as a potential match
                    $this->addUsage($line, $code, 'DYNAMIC', 'dynamic_method_call');
                }
                return; // Don't process further for dynamic calls
            }
            
            if ($methodName) {
                // Check if target symbol contains this method name
                if (str_contains($this->targetSymbol, '::' . $methodName)) {
                    $this->addUsage($line, $code, 'CERTAIN', 'method_call');
                }
                
                // Also check if we're looking for a specific class::method and this matches the method part
                if ($this->targetMethod !== null && $methodName === $this->targetMethod) {
                    $this->addUsage($line, $code, 'CERTAIN', 'method_call');
                }
            }
        }

        // Check for instanceof
        if ($node instanceof Node\Expr\Instanceof_) {
            $className = $this->resolveClassName($node->class);
            if ($className && $this->matchesTarget($className)) {
                $this->addUsage($line, $code, 'CERTAIN', 'instanceof');
            }
        }

        // Check for class constants (including ::class)
        if ($node instanceof Node\Expr\ClassConstFetch) {
            $className = $this->resolveClassName($node->class);
            if ($className && $this->matchesTarget($className)) {
                $this->addUsage($line, $code, 'CERTAIN', 'class_constant');
            }
        }

        // Check for function parameters
        if ($node instanceof Node\Param && $node->type instanceof Node\Name) {
            $paramType = $this->resolveClassName($node->type);
            if ($paramType && $this->matchesTarget($paramType)) {
                $this->addUsage($line, $code, 'CERTAIN', 'type_declaration');
            }
        }
    }

    private function resolveClassName($class): ?string
    {
        if ($class instanceof Node\Name) {
            $name = $class->toString();
            
            // Handle special keywords
            if (in_array($name, ['self', 'parent', 'static'])) {
                return $name;
            }
            
            // Check use statements
            $firstName = $class->getFirst();
            if (isset($this->useStatements[$firstName])) {
                if (count($class->parts) === 1) {
                    return $this->useStatements[$firstName];
                } else {
                    return $this->useStatements[$firstName] . '\\' . implode('\\', array_slice($class->parts, 1));
                }
            }
            
            // Relative to current namespace
            if (!$class->isFullyQualified() && $this->currentNamespace) {
                return $this->currentNamespace . '\\' . $name;
            }
            
            return $name;
        }
        
        return null;
    }

    private function matchesTarget(string $name): bool
    {
        // Remove leading backslash for comparison
        $normalizedName = ltrim($name, '\\');
        
        return $normalizedName === $this->normalizedTarget || 
               str_ends_with($this->normalizedTarget, '\\' . $normalizedName);
    }

    private function getCodeAtLine(int $line): string
    {
        return isset($this->fileLines[$line - 1]) ? trim($this->fileLines[$line - 1]) : '';
    }

    private function addUsage(int $line, string $code, string $confidence, string $type): void
    {
        $this->usages[] = [
            'file' => $this->filePath,
            'line' => $line,
            'code' => $code,
            'confidence' => $confidence,
            'type' => $type,
            'context' => $this->getContext($line)
        ];
    }

    private function getContext(int $line): array
    {
        $start = max(1, $line - 2);
        $end = min(count($this->fileLines), $line + 2);
        
        $contextLines = [];
        for ($i = $start; $i <= $end; $i++) {
            $contextLines[] = $this->fileLines[$i - 1] ?? '';
        }
        
        return [
            'start' => $start,
            'end' => $end,
            'lines' => $contextLines
        ];
    }

    public function getUsages(): array
    {
        return $this->usages;
    }
}