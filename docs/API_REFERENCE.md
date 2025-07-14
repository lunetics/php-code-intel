# API Reference

## Overview

This document provides complete API documentation for all classes and interfaces in the PHP Code Intelligence Tool.

## Core Classes

### UsageFinder

The main class responsible for finding symbol usages in PHP code.

```php
namespace CodeIntel\Finder;

class UsageFinder
```

#### Constructor

```php
public function __construct(
    private SymbolIndex $index,
    ?ErrorLogger $errorLogger = null
)
```

**Parameters:**
- `$index` - SymbolIndex instance for file indexing
- `$errorLogger` - Optional ErrorLogger for error handling (creates default if null)

#### Methods

##### find()

Find all usages of a symbol across the indexed codebase.

```php
public function find(string $symbolName): array
```

**Parameters:**
- `$symbolName` - Fully qualified symbol name (e.g., "App\\User", "User::getName")

**Returns:**
Array of usage arrays with structure:
```php
[
    'file' => '/path/to/file.php',
    'line' => 42,
    'code' => '$user = new User();',
    'confidence' => 'CERTAIN',
    'type' => 'instantiation',
    'context' => [
        'start' => 40,
        'end' => 44,
        'lines' => ['...', 'actual code', '...']
    ]
]
```

**Example:**
```php
$finder = new UsageFinder($symbolIndex);
$usages = $finder->find('App\\Models\\User');

foreach ($usages as $usage) {
    echo "{$usage['file']}:{$usage['line']} - {$usage['confidence']}\n";
}
```

##### getErrorLogger()

Get the error logger instance for accessing error information.

```php
public function getErrorLogger(): ErrorLogger
```

**Returns:** ErrorLogger instance

**Example:**
```php
$usages = $finder->find('SomeClass');
$errorLogger = $finder->getErrorLogger();

if ($errorLogger->hasExceededErrorThreshold(100, 0.1)) {
    echo "Too many errors detected during search\n";
}
```

---

### SymbolIndex

Manages the indexing and storage of PHP symbols for fast lookup.

```php
namespace CodeIntel\Index;

class SymbolIndex
```

#### Constructor

```php
public function __construct()
```

#### Methods

##### addFile()

Add a file to the index by parsing its symbols.

```php
public function addFile(string $filePath): void
```

**Parameters:**
- `$filePath` - Absolute path to PHP file

**Throws:** `InvalidArgumentException` if file doesn't exist

**Example:**
```php
$index = new SymbolIndex();
$index->addFile('/path/to/User.php');
```

##### removeFile()

Remove a file and its symbols from the index.

```php
public function removeFile(string $filePath): void
```

**Parameters:**
- `$filePath` - Path to file to remove

##### getIndexedFiles()

Get list of all indexed files.

```php
public function getIndexedFiles(): array
```

**Returns:** Array of indexed file paths

##### hasSymbol()

Check if a symbol exists in the index.

```php
public function hasSymbol(string $symbolName): bool
```

**Parameters:**
- `$symbolName` - Symbol name to check

**Returns:** True if symbol exists

##### getSymbols()

Get all symbols of a specific type.

```php
public function getSymbols(?string $type = null): array
```

**Parameters:**
- `$type` - Optional symbol type filter ('class', 'method', 'property', etc.)

**Returns:** Array of symbol information

##### clear()

Clear all indexed data.

```php
public function clear(): void
```

---

### ConfidenceScorer

Calculates confidence levels for symbol usage detection.

```php
namespace CodeIntel\Finder;

class ConfidenceScorer
```

#### Constructor

```php
public function __construct()
```

#### Methods

##### score()

Calculate confidence level for a code snippet.

```php
public function score(string $code): string
```

**Parameters:**
- `$code` - Code snippet to analyze

**Returns:** Confidence level string:
- `'CERTAIN'` - Direct, unambiguous usage
- `'PROBABLE'` - Likely usage with type hints
- `'POSSIBLE'` - Dynamic but detectable usage  
- `'DYNAMIC'` - Magic methods or runtime-determined

**Examples:**
```php
$scorer = new ConfidenceScorer();

echo $scorer->score('new User()');              // CERTAIN
echo $scorer->score('$user->getName()');        // PROBABLE  
echo $scorer->score('new $className()');        // POSSIBLE
echo $scorer->score('$obj->__call($method)');   // DYNAMIC
```

##### getPatterns()

Get all confidence scoring patterns.

```php
public function getPatterns(): array
```

**Returns:** Array of regex patterns and their confidence levels

---

### UsageVisitor

AST visitor that detects symbol usages during tree traversal.

```php
namespace CodeIntel\Parser;

class UsageVisitor extends NodeVisitorAbstract
```

#### Constructor

```php
public function __construct(
    private string $targetSymbol,
    private string $filePath,
    private string $sourceCode
)
```

**Parameters:**
- `$targetSymbol` - Symbol to search for
- `$filePath` - Current file path
- `$sourceCode` - Full source code for context

#### Methods

##### getUsages()

Get all detected usages.

```php
public function getUsages(): array
```

**Returns:** Array of usage information

##### enterNode()

Process an AST node (called automatically during traversal).

```php
public function enterNode(Node $node): ?int
```

**Parameters:**
- `$node` - AST node to process

**Returns:** Traversal instruction or null

---

## Error Handling Classes

### ErrorLogger

PSR-3 compatible error logging with categorization and reporting.

```php
namespace CodeIntel\Error;

class ErrorLogger
```

#### Constructor

```php
public function __construct(
    int $maxErrors = 1000,
    ErrorSeverity $minSeverity = ErrorSeverity::INFO
)
```

**Parameters:**
- `$maxErrors` - Maximum errors to store (older errors removed)
- `$minSeverity` - Minimum severity level to log

#### Methods

##### log()

Log an error context.

```php
public function log(ErrorContext $context): void
```

**Parameters:**
- `$context` - ErrorContext instance with error details

##### logParseError()

Log a PHP parser error.

```php
public function logParseError(
    string $filePath,
    \PhpParser\Error $error,
    ?string $codeSnippet = null
): void
```

##### logSyntaxError()

Log a syntax error.

```php
public function logSyntaxError(
    string $filePath,
    string $message,
    ?int $lineNumber = null,
    ?string $codeSnippet = null
): void
```

##### logIoError()

Log an I/O error.

```php
public function logIoError(
    string $filePath,
    string $message,
    ?\Throwable $exception = null
): void
```

##### logMemoryError()

Log a memory-related error.

```php
public function logMemoryError(
    string $filePath,
    string $message,
    ?int $memoryUsage = null,
    ?\Throwable $exception = null
): void
```

##### logTimeoutError()

Log a timeout error.

```php
public function logTimeoutError(
    string $filePath,
    string $message,
    float $executionTime
): void
```

##### getErrors()

Get all logged errors.

```php
public function getErrors(): array
```

**Returns:** Array of ErrorContext objects

##### getErrorsByCategory()

Get errors filtered by category.

```php
public function getErrorsByCategory(ErrorCategory $category): array
```

##### getErrorsBySeverity()

Get errors filtered by severity.

```php
public function getErrorsBySeverity(ErrorSeverity $severity): array
```

##### getErrorCount()

Get total error count or count by category.

```php
public function getErrorCount(?ErrorCategory $category = null): int
```

##### getErrorSummary()

Get comprehensive error statistics.

```php
public function getErrorSummary(): array
```

**Returns:**
```php
[
    'total' => 15,
    'byCategory' => ['syntax' => 8, 'io' => 5],
    'bySeverity' => ['warning' => 8, 'error' => 7],
    'criticalFiles' => ['/path/file.php' => 5] // Files with 3+ errors
]
```

##### hasExceededErrorThreshold()

Check if error rate exceeds threshold.

```php
public function hasExceededErrorThreshold(int $totalFiles, float $threshold = 0.2): bool
```

**Parameters:**
- `$totalFiles` - Total number of files processed
- `$threshold` - Error rate threshold (0.0-1.0)

**Returns:** True if error rate exceeds threshold

##### hasCriticalErrors()

Check for critical errors.

```php
public function hasCriticalErrors(): bool
```

##### clear()

Clear all logged errors.

```php
public function clear(): void
```

##### toArray()

Export errors to array format.

```php
public function toArray(): array
```

##### displaySummary()

Display formatted error summary using Symfony Console.

```php
public function displaySummary(SymfonyStyle $io): void
```

---

### ErrorContext

Immutable container for detailed error information.

```php
namespace CodeIntel\Error;

readonly class ErrorContext
```

#### Constructor

```php
public function __construct(
    public string $filePath,
    public ErrorCategory $category,
    public ErrorSeverity $severity,
    public string $message,
    public ?\Throwable $exception = null,
    public ?int $lineNumber = null,
    public ?int $columnNumber = null,
    public ?string $codeSnippet = null,
    public ?float $memoryUsage = null,
    public ?float $executionTime = null,
    public array $additionalData = []
)
```

#### Static Factory Methods

##### fromThrowable()

Create context from throwable.

```php
public static function fromThrowable(
    string $filePath,
    ErrorCategory $category,
    \Throwable $exception,
    array $additionalData = []
): self
```

##### syntaxError()

Create syntax error context.

```php
public static function syntaxError(
    string $filePath,
    string $message,
    ?int $lineNumber = null,
    ?string $codeSnippet = null
): self
```

##### ioError()

Create I/O error context.

```php
public static function ioError(
    string $filePath,
    string $message,
    ?\Throwable $exception = null
): self
```

##### memoryError()

Create memory error context.

```php
public static function memoryError(
    string $filePath,
    string $message,
    float $memoryUsage,
    ?\Throwable $exception = null
): self
```

##### timeoutError()

Create timeout error context.

```php
public static function timeoutError(
    string $filePath,
    string $message,
    float $executionTime
): self
```

#### Methods

##### getFormattedMessage()

Get formatted error message with context.

```php
public function getFormattedMessage(): string
```

##### toArray()

Convert to array for serialization.

```php
public function toArray(): array
```

---

### ErrorCategory

Enum defining error categories.

```php
namespace CodeIntel\Error;

enum ErrorCategory: string
```

#### Cases

```php
case SYNTAX_ERROR = 'syntax';
case IO_ERROR = 'io';
case MEMORY_ERROR = 'memory';
case TIMEOUT_ERROR = 'timeout';
case PARSER_ERROR = 'parser';
case CONFIGURATION_ERROR = 'configuration';
case INDEX_ERROR = 'index';
```

#### Methods

##### getDescription()

Get human-readable description.

```php
public function getDescription(): string
```

##### getSeverity()

Get default severity for this category.

```php
public function getSeverity(): ErrorSeverity
```

##### shouldStopProcessing()

Check if this error type should stop processing.

```php
public function shouldStopProcessing(): bool
```

**Returns:** True for MEMORY_ERROR and CONFIGURATION_ERROR

---

### ErrorSeverity

Enum defining severity levels.

```php
namespace CodeIntel\Error;

enum ErrorSeverity: string
```

#### Cases

```php
case INFO = 'info';
case WARNING = 'warning';
case ERROR = 'error';
case CRITICAL = 'critical';
```

#### Methods

##### getPriority()

Get numeric priority for sorting.

```php
public function getPriority(): int
```

**Returns:** 10 (INFO), 20 (WARNING), 30 (ERROR), 40 (CRITICAL)

##### isAtLeast()

Check if this severity meets minimum level.

```php
public function isAtLeast(ErrorSeverity $minimum): bool
```

##### getConsoleColor()

Get console color for display.

```php
public function getConsoleColor(): string
```

**Returns:** 'blue', 'yellow', 'red', or 'magenta'

---

## Console Classes

### Application

Main console application.

```php
namespace CodeIntel\Console;

class Application extends \Symfony\Component\Console\Application
```

#### Constructor

```php
public function __construct()
```

### FindUsagesCommand

Command for finding symbol usages.

```php
namespace CodeIntel\Console\Command;

class FindUsagesCommand extends Command
```

#### Configuration

- **Name:** `find-usages`
- **Arguments:** `symbol` (required) - Symbol name to search for
- **Options:**
  - `--path, -p` - Search paths (default: current directory)
  - `--format, -f` - Output format: json|table|claude (default: claude)
  - `--exclude, -e` - Exclude paths
  - `--confidence, -c` - Minimum confidence level
  - `--verbose, -v` - Verbose output

#### Usage Examples

```bash
# Basic usage
php bin/php-code-intel find-usages "App\\User"

# With options
php bin/php-code-intel find-usages "User::getName" \
  --path=src/ \
  --format=json \
  --confidence=CERTAIN \
  --exclude=vendor
```

### IndexCommand

Command for indexing project files.

```php
namespace CodeIntel\Console\Command;

class IndexCommand extends Command
```

#### Configuration

- **Name:** `index`
- **Arguments:** `paths` (optional, array) - Paths to index
- **Options:**
  - `--exclude, -e` - Exclude paths
  - `--stats, -s` - Show statistics
  - `--verbose, -v` - Verbose output

### VersionCommand

Command for displaying version information.

```php
namespace CodeIntel\Console\Command;

class VersionCommand extends Command
```

#### Configuration

- **Name:** `version`
- **Options:**
  - `--verbose, -v` - Show detailed system information

---

## Usage Patterns

### Basic Symbol Finding

```php
use CodeIntel\Index\SymbolIndex;
use CodeIntel\Finder\UsageFinder;

// Create index and finder
$index = new SymbolIndex();
$index->addFile('/path/to/User.php');
$index->addFile('/path/to/UserService.php');

$finder = new UsageFinder($index);

// Find usages
$usages = $finder->find('App\\Models\\User');

foreach ($usages as $usage) {
    printf(
        "%s:%d - %s (%s)\n",
        basename($usage['file']),
        $usage['line'],
        $usage['code'],
        $usage['confidence']
    );
}
```

### Error Handling Integration

```php
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorSeverity;

// Create logger with custom settings
$errorLogger = new ErrorLogger(
    maxErrors: 500,
    minSeverity: ErrorSeverity::WARNING
);

$finder = new UsageFinder($index, $errorLogger);
$usages = $finder->find('SomeClass');

// Check for errors
if ($errorLogger->hasCriticalErrors()) {
    echo "Critical errors detected:\n";
    foreach ($errorLogger->getErrorsBySeverity(ErrorSeverity::CRITICAL) as $error) {
        echo "- {$error->filePath}: {$error->message}\n";
    }
}
```

### Confidence Scoring

```php
use CodeIntel\Finder\ConfidenceScorer;

$scorer = new ConfidenceScorer();

$patterns = [
    'new User()',
    '$user->getName()',
    'new $className()',
    '$obj->__call($method)'
];

foreach ($patterns as $code) {
    printf("%-25s -> %s\n", $code, $scorer->score($code));
}
```

### Custom AST Processing

```php
use CodeIntel\Parser\UsageVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$code = file_get_contents('/path/to/file.php');
$ast = $parser->parse($code);

$visitor = new UsageVisitor('App\\User', '/path/to/file.php', $code);
$traverser = new NodeTraverser();
$traverser->addVisitor($visitor);
$traverser->traverse($ast);

$usages = $visitor->getUsages();
```

---

**This API reference provides complete documentation for integrating and extending the PHP Code Intelligence Tool.**