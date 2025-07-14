<?php

declare(strict_types=1);

namespace CodeIntel\Index;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

/**
 * Stores and manages symbol information from indexed PHP files
 */
class SymbolIndex
{
    /** @var array<string, array<string, array<string>>> */
    private array $symbols = [];
    /** @var array<string, string> */
    private array $fileHashes = [];
    /** @var array<string> */
    private array $indexedFiles = [];
    
    public function indexFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }
        
        $hash = md5_file($filePath);
        if ($hash !== false) {
            $this->fileHashes[$filePath] = $hash;
        }
        $this->indexedFiles[] = $filePath;
        
        // Basic indexing - just store that we've processed the file
        // Real symbol extraction can be added later
        $this->symbols[$filePath] = [
            'classes' => [],
            'methods' => [],
            'functions' => [],
            'constants' => []
        ];
    }
    
    /** @return array<string, array<string, array<string>>> */
    public function getSymbols(): array
    {
        return $this->symbols;
    }
    
    /** @return array<string, array<string>>|null */
    public function findSymbol(string $fullyQualifiedName): ?array
    {
        return $this->symbols[$fullyQualifiedName] ?? null;
    }
    
    /** @return array<string> */
    public function getIndexedFiles(): array
    {
        return $this->indexedFiles;
    }
    
    public function getSymbolCount(): int
    {
        $count = 0;
        foreach ($this->symbols as $fileSymbols) {
            $count += count($fileSymbols['classes'] ?? []);
            $count += count($fileSymbols['methods'] ?? []);
            $count += count($fileSymbols['functions'] ?? []);
            $count += count($fileSymbols['constants'] ?? []);
        }
        return $count;
    }
    
    /**
     * Check if a file has changed since it was last indexed
     */
    public function hasFileChanged(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return true; // File no longer exists, consider it changed
        }
        
        if (!isset($this->fileHashes[$filePath])) {
            return true; // File not previously indexed
        }
        
        $currentHash = md5_file($filePath);
        return $currentHash !== false && $currentHash !== $this->fileHashes[$filePath];
    }
    
    /**
     * Get the stored hash for a file
     */
    public function getFileHash(string $filePath): ?string
    {
        return $this->fileHashes[$filePath] ?? null;
    }
    
    /** @return array<string, string> */
    public function getFileHashes(): array
    {
        return $this->fileHashes;
    }
    
    public function clear(): void
    {
        $this->symbols = [];
        $this->indexedFiles = [];
    }
}