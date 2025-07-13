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
    /**
     * @var array<string, array{classes: array<mixed>, methods: array<mixed>, functions: array<mixed>, constants: array<mixed>}>
     */
    private array $symbols = [];
    /**
     * @var array<string>
     */
    private array $indexedFiles = [];
    
    public function indexFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
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
    
    /**
     * @return array<string, array{classes: array<mixed>, methods: array<mixed>, functions: array<mixed>, constants: array<mixed>}>
     */
    public function getSymbols(): array
    {
        return $this->symbols;
    }
    
    /**
     * @return array{classes: array<mixed>, methods: array<mixed>, functions: array<mixed>, constants: array<mixed>}|null
     */
    public function findSymbol(string $fullyQualifiedName): ?array
    {
        return $this->symbols[$fullyQualifiedName] ?? null;
    }
    
    /**
     * @return array<string>
     */
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
    
    public function clear(): void
    {
        $this->symbols = [];
        $this->indexedFiles = [];
    }
}