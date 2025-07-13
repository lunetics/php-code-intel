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

    public function __construct(string $targetSymbol, string $filePath)
    {
        $this->targetSymbol = $targetSymbol;
        $this->filePath = $filePath;
        $this->fileLines = file($filePath, FILE_IGNORE_NEW_LINES) ?: [];
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
                if ($this->matchesTarget($fullName) || $this->matchesTarget($className)) {
                    $this->addUsage($line, $code, 'CERTAIN', 'static_call');
                }
            }
        }

        // Check for method calls
        if ($node instanceof Node\Expr\MethodCall) {
            $methodName = $node->name instanceof Node\Identifier ? $node->name->name : null;
            if ($methodName && str_contains($this->targetSymbol, '::' . $methodName)) {
                $confidence = str_contains($code, '?->') ? 'PROBABLE' : 'CERTAIN';
                $this->addUsage($line, $code, $confidence, 'method_call');
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
        $name = ltrim($name, '\\');
        $target = ltrim($this->targetSymbol, '\\');
        
        return $name === $target || str_ends_with($target, '\\' . $name);
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