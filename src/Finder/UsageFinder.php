<?php

declare(strict_types=1);

namespace CodeIntel\Finder;

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
    
    public function __construct(
        private SymbolIndex $index
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->scorer = new ConfidenceScorer();
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
                return [];
            }
            
            $ast = $this->parser->parse($code);
            if ($ast === null) {
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
            // Parsing error - skip this file silently for now
            // In production, this could be logged
            return [];
        }
    }
}