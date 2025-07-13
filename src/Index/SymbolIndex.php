<?php

declare(strict_types=1);

namespace CodeIntel\Index;

/**
 * Stores and manages symbol information from indexed PHP files
 */
class SymbolIndex
{
    private array $symbols = [];
    private array $fileHashes = [];
    
    public function indexFile(string $filePath): void
    {
        // TODO: Implement file indexing
        // This will parse the file and extract symbols
        throw new \RuntimeException('Not implemented yet');
    }
    
    public function getSymbols(): array
    {
        return $this->symbols;
    }
    
    public function findSymbol(string $fullyQualifiedName): ?array
    {
        return $this->symbols[$fullyQualifiedName] ?? null;
    }
    
    public function clear(): void
    {
        $this->symbols = [];
        $this->fileHashes = [];
    }
}