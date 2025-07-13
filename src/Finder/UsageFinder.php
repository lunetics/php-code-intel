<?php

declare(strict_types=1);

namespace CodeIntel\Finder;

use CodeIntel\Index\SymbolIndex;
use CodeIntel\Parser\UsageVisitor;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

/**
 * Finds usages of symbols in the indexed codebase
 */
class UsageFinder
{
    private $parser;
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
     * @return array Array of usage information
     */
    public function find(string $symbolName): array
    {
        $allUsages = [];
        $indexedFiles = $this->index->getIndexedFiles();
        
        foreach ($indexedFiles as $filePath) {
            $usages = $this->findUsagesInFile($symbolName, $filePath);
            $allUsages = array_merge($allUsages, $usages);
        }
        
        return $allUsages;
    }
    
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
            
            $visitor = new UsageVisitor($symbolName, $filePath);
            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);
            
            $usages = $visitor->getUsages();
            
            // Apply confidence scoring
            foreach ($usages as &$usage) {
                $usage['confidence'] = $this->scorer->score($usage['code']);
            }
            
            return $usages;
            
        } catch (Error $e) {
            // Parsing error - skip this file
            return [];
        }
    }
}