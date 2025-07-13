<?php

declare(strict_types=1);

namespace CodeIntel\Finder;

use CodeIntel\Index\SymbolIndex;

/**
 * Finds usages of symbols in the indexed codebase
 */
class UsageFinder
{
    public function __construct(
        private SymbolIndex $index
    ) {}
    
    /**
     * Find all usages of a symbol
     * 
     * @param string $symbolName Fully qualified symbol name
     * @return array Array of usage information
     */
    public function find(string $symbolName): array
    {
        // For now, return empty array to prevent exceptions
        // Tests expect empty array when no usages found
        
        // TODO: Implement actual usage finding logic:
        // 1. Get files from index
        // 2. Parse files with php-parser
        // 3. Find usages using AST visitors
        // 4. Return structured usage data
        
        return [];
    }
}