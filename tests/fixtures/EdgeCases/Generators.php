<?php

declare(strict_types=1);

namespace TestFixtures\EdgeCases;

use Generator;

// Simple generator functions
function simpleGenerator(): Generator
{
    yield 1;
    yield 2;
    yield 3;
}

function rangeGenerator(int $start, int $end): Generator
{
    for ($i = $start; $i <= $end; $i++) {
        yield $i;
    }
}

// Generators with keys
function keyValueGenerator(): Generator
{
    yield 'first' => 1;
    yield 'second' => 2;
    yield 'third' => 3;
}

function mixedKeyGenerator(): Generator
{
    yield 1;              // Auto key: 0
    yield 'a' => 2;       // Explicit key: 'a'
    yield 3;              // Auto key: 1
    yield 'b' => 4;       // Explicit key: 'b'
}

// Generator delegation (yield from)
function innerGenerator(): Generator
{
    yield 1;
    yield 2;
}

function outerGenerator(): Generator
{
    yield 0;
    yield from innerGenerator();
    yield 3;
}

function delegateToArray(): Generator
{
    yield from [10, 20, 30];
    yield from ['a' => 40, 'b' => 50];
}

// Nested yield from
function level1(): Generator
{
    yield 'L1-1';
    yield 'L1-2';
}

function level2(): Generator
{
    yield 'L2-1';
    yield from level1();
    yield 'L2-2';
}

function level3(): Generator
{
    yield 'L3-1';
    yield from level2();
    yield 'L3-2';
}

// Generators in classes
class GeneratorClass
{
    private int $counter = 0;
    
    public function instanceGenerator(): Generator
    {
        for ($i = 0; $i < 3; $i++) {
            yield $this->counter++;
        }
    }
    
    public static function staticGenerator(): Generator
    {
        yield 'static1';
        yield 'static2';
        yield 'static3';
    }
    
    public function complexGenerator(array $data): Generator
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                yield from $this->complexGenerator($value);
            } else {
                yield $key => $value;
            }
        }
    }
}

// Return values from generators
function generatorWithReturn(): Generator
{
    yield 1;
    yield 2;
    yield 3;
    return 'done';
}

function processGeneratorReturn(): string
{
    $gen = generatorWithReturn();
    foreach ($gen as $value) {
        // Process yielded values
    }
    return $gen->getReturn();
}

// Generator with send()
function interactiveGenerator(): Generator
{
    $value = yield 'start';
    while ($value !== 'stop') {
        $value = yield "Received: $value";
    }
    yield 'stopped';
}

// Stateful generator
function statefulGenerator(): Generator
{
    $state = ['count' => 0, 'sum' => 0];
    
    while (true) {
        $value = yield $state;
        if ($value === null) {
            break;
        }
        $state['count']++;
        $state['sum'] += $value;
    }
    
    return $state;
}

// Infinite generator
function infiniteSequence(int $start = 0): Generator
{
    $current = $start;
    while (true) {
        yield $current++;
    }
}

// Fibonacci generator
function fibonacci(): Generator
{
    $a = 0;
    $b = 1;
    
    while (true) {
        yield $a;
        [$a, $b] = [$b, $a + $b];
    }
}

// Generator with try-catch-finally
function generatorWithExceptionHandling(): Generator
{
    try {
        yield 'before';
        yield 'middle';
        throw new \Exception('Test exception');
        yield 'after'; // Never reached
    } catch (\Exception $e) {
        yield 'caught: ' . $e->getMessage();
    } finally {
        yield 'finally';
    }
}

// File reading generator
function readFileByLine(string $filename): Generator
{
    $handle = fopen($filename, 'r');
    if (!$handle) {
        throw new \RuntimeException("Cannot open file: $filename");
    }
    
    try {
        while (($line = fgets($handle)) !== false) {
            yield rtrim($line, "\n\r");
        }
    } finally {
        fclose($handle);
    }
}

// Generator with references
function &referenceGenerator(array &$data): Generator
{
    foreach ($data as &$value) {
        yield $value;
    }
}

// Processing generator
function processingGenerator(iterable $input): Generator
{
    foreach ($input as $key => $value) {
        // Simulate processing
        $processed = strtoupper((string)$value);
        yield $key => $processed;
    }
}

// Generator composition
function take(Generator $generator, int $limit): Generator
{
    $count = 0;
    foreach ($generator as $key => $value) {
        if ($count >= $limit) {
            break;
        }
        yield $key => $value;
        $count++;
    }
}

function filter(Generator $generator, callable $predicate): Generator
{
    foreach ($generator as $key => $value) {
        if ($predicate($value, $key)) {
            yield $key => $value;
        }
    }
}

function map(Generator $generator, callable $mapper): Generator
{
    foreach ($generator as $key => $value) {
        yield $key => $mapper($value, $key);
    }
}

// Chained generators example
function chainedExample(): Generator
{
    $numbers = infiniteSequence(1);
    $evens = filter($numbers, fn($n) => $n % 2 === 0);
    $squared = map($evens, fn($n) => $n * $n);
    $limited = take($squared, 5);
    
    yield from $limited;
}

// Generator in anonymous class
$anonymousWithGenerator = new class {
    public function generate(): Generator
    {
        yield 'anonymous1';
        yield 'anonymous2';
    }
};

// Generator with type declarations
function typedGenerator(): Generator
{
    yield 1;
    yield 2.5;
    yield 'string';
    yield true;
    yield null;
}

// Strict typed generator (PHP 7.0+)
function strictIntGenerator(): \Generator
{
    yield 1;
    yield 2;
    yield 3;
}

// Generator with nullable return type
function nullableReturnGenerator(): ?Generator
{
    if (rand(0, 1)) {
        yield 1;
        yield 2;
    }
    return null;
}

// Complex nested structure generator
class TreeNode
{
    public function __construct(
        public mixed $value,
        public array $children = []
    ) {}
}

function traverseTree(TreeNode $node): Generator
{
    yield $node->value;
    
    foreach ($node->children as $child) {
        yield from traverseTree($child);
    }
}

// Generator with match expression (PHP 8.0+)
function matchGenerator(array $items): Generator
{
    foreach ($items as $item) {
        yield match(gettype($item)) {
            'integer' => $item * 2,
            'string' => strtoupper($item),
            'array' => count($item),
            default => $item
        };
    }
}

// Async-like generator pattern
function asyncOperation(int $id): Generator
{
    yield "Starting operation $id";
    
    // Simulate async work
    for ($i = 0; $i < 3; $i++) {
        yield "Operation $id progress: " . ($i + 1) . "/3";
    }
    
    yield "Completed operation $id";
}

function runConcurrent(array $generators): Generator
{
    while ($generators) {
        foreach ($generators as $key => $generator) {
            if ($generator->valid()) {
                yield $generator->current();
                $generator->next();
            } else {
                unset($generators[$key]);
            }
        }
    }
}

// Generator with attributes (PHP 8.0+)
#[\Attribute]
class GeneratorAttribute {}

#[GeneratorAttribute]
function attributedGenerator(): Generator
{
    yield 'attributed';
}

// Memory-efficient data processing
function processLargeDataset(): Generator
{
    // Simulate processing large dataset without loading all into memory
    for ($chunk = 0; $chunk < 1000; $chunk++) {
        $data = range($chunk * 100, ($chunk + 1) * 100 - 1);
        
        foreach ($data as $item) {
            if ($item % 7 === 0) {
                yield $item;
            }
        }
        
        // Free memory after each chunk
        unset($data);
    }
}