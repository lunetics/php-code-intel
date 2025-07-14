<?php

declare(strict_types=1);

namespace CodeIntel\Finder;

use CodeIntel\Error\ErrorLogger;
use CodeIntel\Index\SymbolIndex;
use CodeIntel\Parser\UsageVisitor;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

/**
 * Finds usages of symbols in the indexed codebase using AST analysis
 * 
 * This class coordinates the symbol search process by:
 * 1. Parsing PHP files using nikic/php-parser
 * 2. Analyzing AST nodes with UsageVisitor
 * 3. Scoring confidence levels with ConfidenceScorer
 * 4. Returning structured usage data for Claude Code
 */
class UsageFinder
{
    private Parser $parser;
    private ConfidenceScorer $scorer;
    private ErrorLogger $errorLogger;
    
    public function __construct(
        private SymbolIndex $index,
        ?ErrorLogger $errorLogger = null
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->scorer = new ConfidenceScorer();
        $this->errorLogger = $errorLogger ?? new ErrorLogger();
    }
    
    /**
     * Find all usages of a symbol
     * 
     * @param string $symbolName Fully qualified symbol name
     * @return array<array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}}>
     */
    public function find(string $symbolName): array
    {
        $allUsages = [];
        $indexedFiles = $this->index->getIndexedFiles();
        
        foreach ($indexedFiles as $filePath) {
            $usages = $this->findUsagesInFile($symbolName, $filePath);
            // Use array_push with spread operator for better performance
            array_push($allUsages, ...$usages);
        }
        
        return $allUsages;
    }
    
    /**
     * @return array<array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}}>
     */
    private function findUsagesInFile(string $symbolName, string $filePath): array
    {
        try {
            $code = file_get_contents($filePath);
            if ($code === false) {
                $this->errorLogger->logIoError($filePath, 'Failed to read file');
                return [];
            }
            
            $ast = $this->parser->parse($code);
            if ($ast === null) {
                $this->errorLogger->logParseError($filePath, new Error('Failed to parse AST'), null);
                return [];
            }
            
            $visitor = new UsageVisitor($symbolName, $filePath, $code);
            
            // Create fresh traverser for each file to avoid visitor conflicts
            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);
            
            $usages = $visitor->getUsages();
            
            // Apply confidence scoring in place for better memory usage
            foreach ($usages as &$usage) {
                $usage['confidence'] = $this->scorer->score($usage['code']);
            }
            unset($usage); // Break reference to last element
            
            return $usages;
            
        } catch (Error $e) {
            // Log parsing error with context
            $lineNumber = $e->getStartLine() ?? null;
            $codeSnippet = null;
            if (is_string($code)) {
                $codeSnippet = $this->getCodeSnippet($code, $lineNumber);
            }
            $this->errorLogger->logParseError($filePath, $e, $codeSnippet);
            return [];
        } catch (\Throwable $e) {
            // Log unexpected errors
            $this->errorLogger->logIoError($filePath, 'Unexpected error during processing: ' . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get a code snippet around a specific line for error context
     */
    private function getCodeSnippet(string $code, ?int $lineNumber, int $context = 2): ?string
    {
        if ($lineNumber === null || empty($code)) {
            return null;
        }

        $lines = explode("\n", $code);
        $totalLines = count($lines);
        
        if ($lineNumber < 1 || $lineNumber > $totalLines) {
            return null;
        }

        $start = max(1, $lineNumber - $context);
        $end = min($totalLines, $lineNumber + $context);
        
        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $prefix = ($i === $lineNumber) ? '>>> ' : '    ';
            $snippet[] = sprintf('%s%d: %s', $prefix, $i, $lines[$i - 1]);
        }

        return implode("\n", $snippet);
    }

    /**
     * Get the error logger for external access
     */
    public function getErrorLogger(): ErrorLogger
    {
        return $this->errorLogger;
    }
}