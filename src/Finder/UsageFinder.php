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
        // TODO: Implement usage finding
        // This will search through indexed files for references to the symbol
        return [];
    }
}