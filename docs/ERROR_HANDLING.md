# Error Handling System Guide

## Overview

The PHP Code Intelligence Tool features a comprehensive, PSR-3 compatible error handling system designed for robust operation across large codebases. The system provides detailed error categorization, severity levels, and actionable reporting.

## Architecture

The error handling system consists of four main components:

```
src/Error/
├── ErrorCategory.php     # Error type classification
├── ErrorSeverity.php     # Priority and severity levels  
├── ErrorContext.php      # Detailed error information
└── ErrorLogger.php       # PSR-3 compatible logging
```

## Error Categories

### Available Categories

| Category | Description | Severity | Stop Processing |
|----------|-------------|----------|-----------------|
| `SYNTAX_ERROR` | PHP syntax errors and parse failures | WARNING | No |
| `IO_ERROR` | File system access and reading errors | ERROR | No |
| `MEMORY_ERROR` | Memory limit exceeded or allocation failures | CRITICAL | Yes |
| `TIMEOUT_ERROR` | Processing timeout exceeded | ERROR | No |
| `PARSER_ERROR` | AST parsing and analysis errors | WARNING | No |
| `CONFIGURATION_ERROR` | Invalid configuration or setup | CRITICAL | Yes |
| `INDEX_ERROR` | Symbol indexing and storage errors | ERROR | No |

### Usage Examples

```php
use CodeIntel\Error\ErrorCategory;

// Check if an error should stop processing
if ($error->category->shouldStopProcessing()) {
    return; // Exit early for critical errors
}

// Get error description
$description = ErrorCategory::MEMORY_ERROR->getDescription();
// "Memory allocation error or limit exceeded"

// Get default severity
$severity = ErrorCategory::SYNTAX_ERROR->getSeverity();
// ErrorSeverity::WARNING
```

## Error Severity Levels

### Severity Hierarchy

```php
ErrorSeverity::INFO     // Informational messages
ErrorSeverity::WARNING  // Potential issues, processing continues
ErrorSeverity::ERROR    // Definite problems, may affect results
ErrorSeverity::CRITICAL // Severe issues, processing should stop
```

### Severity Properties

```php
use CodeIntel\Error\ErrorSeverity;

// Check if severity meets threshold
$isAtLeast = ErrorSeverity::ERROR->isAtLeast(ErrorSeverity::WARNING);
// true

// Get priority for sorting
$priority = ErrorSeverity::CRITICAL->getPriority();
// 40 (highest)

// Get console color for display
$color = ErrorSeverity::ERROR->getConsoleColor();
// 'red'
```

## Error Context

The `ErrorContext` class provides comprehensive error information:

### Properties

```php
readonly class ErrorContext
{
    public string $filePath;              // File where error occurred
    public ErrorCategory $category;       // Error category
    public ErrorSeverity $severity;       // Severity level
    public string $message;               // Error message
    public ?\Throwable $exception;        // Original exception (if any)
    public ?int $lineNumber;              // Line number in file
    public ?int $columnNumber;            // Column position
    public ?string $codeSnippet;          // Code context around error
    public ?float $memoryUsage;           // Memory usage at error time
    public ?float $executionTime;         // Execution time until error
    public array $additionalData;         // Extra context data
}
```

### Factory Methods

```php
use CodeIntel\Error\ErrorContext;

// Create from throwable
$context = ErrorContext::fromThrowable(
    filePath: '/path/to/file.php',
    category: ErrorCategory::PARSER_ERROR,
    exception: $parseError,
    additionalData: ['codeSnippet' => $snippet]
);

// Create syntax error
$context = ErrorContext::syntaxError(
    filePath: '/path/to/file.php',
    message: 'Unexpected token',
    lineNumber: 42,
    codeSnippet: 'invalid syntax here'
);

// Create I/O error
$context = ErrorContext::ioError(
    filePath: '/path/to/file.php',
    message: 'Permission denied',
    exception: $fileException
);

// Create memory error
$context = ErrorContext::memoryError(
    filePath: '/path/to/file.php',
    message: 'Memory limit exceeded',
    memoryUsage: 268435456.0  // 256MB
);

// Create timeout error
$context = ErrorContext::timeoutError(
    filePath: '/path/to/file.php',
    message: 'Processing timeout',
    executionTime: 30.5
);
```

## Error Logger

### Basic Usage

```php
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorSeverity;

// Create logger with custom settings
$logger = new ErrorLogger(
    maxErrors: 500,                    // Maximum errors to store
    minSeverity: ErrorSeverity::WARNING // Only log WARNING and above
);

// Log different error types
$logger->logSyntaxError('/path/file.php', 'Missing semicolon', 10);
$logger->logIoError('/path/file.php', 'File not found');
$logger->logMemoryError('/path/file.php', 'Out of memory', 512 * 1024 * 1024);
$logger->logTimeoutError('/path/file.php', 'Timeout reached', 45.2);

// Log with context object
$context = ErrorContext::syntaxError('/path/file.php', 'Parse error');
$logger->log($context);
```

### Advanced Features

```php
// Get error statistics
$totalErrors = $logger->getErrorCount();
$syntaxErrors = $logger->getErrorCount(ErrorCategory::SYNTAX_ERROR);

// Get errors by category
$parseErrors = $logger->getErrorsByCategory(ErrorCategory::PARSER_ERROR);

// Get errors by severity
$criticalErrors = $logger->getErrorsBySeverity(ErrorSeverity::CRITICAL);

// Check for critical issues
if ($logger->hasCriticalErrors()) {
    echo "Critical errors detected!\n";
}

// Check error threshold
if ($logger->hasExceededErrorThreshold(1000, 0.1)) {
    echo "Error rate exceeds 10% of files\n";
}

// Get comprehensive summary
$summary = $logger->getErrorSummary();
/*
Array format:
[
    'total' => 15,
    'byCategory' => ['syntax' => 8, 'io' => 5, 'memory' => 2],
    'bySeverity' => ['warning' => 8, 'error' => 5, 'critical' => 2],
    'criticalFiles' => ['/path/problem.php' => 5] // Files with 3+ errors
]
*/
```

## Console Integration

### Display Error Summary

```php
use Symfony\Component\Console\Style\SymfonyStyle;

// Display formatted error summary
$io = new SymfonyStyle($input, $output);
$logger->displaySummary($io);
```

Example output:
```
Error Summary (15 total)

By Category:
  • Syntax errors in PHP code: 8 (warning)
  • File system access error: 5 (error)  
  • Memory allocation error: 2 (critical)

By Severity:
  • Warning: 8
  • Error: 5
  • Critical: 2

Files with Multiple Errors:
  • problem.php (5 errors)
  • complex.php (3 errors)
```

## Integration with UsageFinder

The `UsageFinder` class has been enhanced with comprehensive error logging:

### Enhanced Error Handling

```php
use CodeIntel\Finder\UsageFinder;
use CodeIntel\Error\ErrorLogger;

// Create with custom error logger
$errorLogger = new ErrorLogger(maxErrors: 1000);
$finder = new UsageFinder($symbolIndex, $errorLogger);

// Find usages with error logging
$usages = $finder->find('App\\User');

// Check for errors after processing
$errorLogger = $finder->getErrorLogger();
if ($errorLogger->hasCriticalErrors()) {
    echo "Critical errors during symbol finding:\n";
    $errorLogger->displaySummary($io);
}
```

### Error Context in File Processing

When file processing fails, detailed context is captured:

```php
// Example error log entry from UsageFinder
$context = ErrorContext::fromThrowable(
    filePath: '/app/src/Service/UserService.php',
    category: ErrorCategory::PARSER_ERROR,
    exception: $parseError,  // Original PhpParser\Error
    additionalData: [
        'codeSnippet' => "
    15: class UserService {
    16:     public function process() {
>>> 17:         return $this->invalid syntax here
    18:         // Missing closing brace
    19:     }
        "
    ]
);
```

## Configuration and Customization

### Custom Error Thresholds

```php
// Stop processing if error rate exceeds 20%
$errorLogger = new ErrorLogger();
$totalFiles = count($projectFiles);

foreach ($projectFiles as $file) {
    // ... process file ...
    
    if ($errorLogger->hasExceededErrorThreshold($totalFiles, 0.2)) {
        echo "Too many errors, stopping processing\n";
        break;
    }
}
```

### Memory Management

```php
// Prevent memory issues with large projects
$errorLogger = new ErrorLogger(
    maxErrors: 100,  // Limit stored errors
    minSeverity: ErrorSeverity::ERROR  // Only log ERROR and CRITICAL
);

// Clear errors periodically
if ($errorLogger->getErrorCount() > 50) {
    $summary = $errorLogger->getErrorSummary();
    // Save summary for reporting
    $errorLogger->clear();
}
```

### Export and Serialization

```php
// Export errors for external analysis
$errorData = $errorLogger->toArray();

// Save to JSON
file_put_contents('error-report.json', json_encode($errorData, JSON_PRETTY_PRINT));

// Export format example:
[
    {
        "filePath": "/app/src/Service.php",
        "category": "syntax",
        "severity": "warning", 
        "message": "Unexpected token T_STRING",
        "lineNumber": 42,
        "codeSnippet": "invalid syntax",
        "exceptionClass": "PhpParser\\Error",
        "exceptionMessage": "Syntax error, unexpected T_STRING"
    }
]
```

## Best Practices

### 1. Early Error Detection

```php
// Check for critical errors early
if ($errorLogger->hasCriticalErrors()) {
    throw new \RuntimeException('Critical errors prevent processing');
}
```

### 2. Graceful Degradation

```php
foreach ($files as $file) {
    try {
        $result = processFile($file);
    } catch (\Throwable $e) {
        $errorLogger->logIoError($file, $e->getMessage(), $e);
        
        // Continue processing other files
        continue;
    }
}
```

### 3. Error Reporting

```php
// Generate comprehensive error report
$summary = $errorLogger->getErrorSummary();

if ($summary['total'] > 0) {
    $report = [
        'timestamp' => date('c'),
        'project' => 'my-project',
        'totalFiles' => count($allFiles),
        'errors' => $summary,
        'details' => $errorLogger->toArray()
    ];
    
    file_put_contents('error-report.json', json_encode($report, JSON_PRETTY_PRINT));
}
```

### 4. Performance Monitoring

```php
// Track memory usage in error context
$memoryBefore = memory_get_usage(true);

try {
    $result = heavyProcessing($data);
} catch (\Throwable $e) {
    $memoryUsage = memory_get_usage(true) - $memoryBefore;
    
    $errorLogger->logMemoryError(
        filePath: $currentFile,
        message: 'Processing failed: ' . $e->getMessage(),
        memoryUsage: (float)$memoryUsage,
        exception: $e
    );
}
```

## Testing Error Handling

The error handling system includes comprehensive tests:

### Test Coverage

- **ErrorCategoryTest**: 16 tests for error categorization
- **ErrorLoggerTest**: 16 tests for logging functionality  
- **Test fixtures**: Realistic error scenarios

### Running Error Handling Tests

```bash
# Run error handling test suite
vendor/bin/phpunit tests/Unit/ErrorHandling/

# Run with coverage
vendor/bin/phpunit --coverage-html coverage tests/Unit/ErrorHandling/

# Test specific error scenarios
vendor/bin/phpunit tests/Unit/ErrorHandling/ErrorLoggerTest.php::test_error_summary
```

## Migration Guide

### From Silent Error Handling

If upgrading from a version with silent error handling:

```php
// Before: Silent failure
try {
    $ast = $parser->parse($code);
} catch (Error $e) {
    return []; // Silent failure
}

// After: Comprehensive logging
try {
    $ast = $parser->parse($code);
} catch (Error $e) {
    $lineNumber = $e->getStartLine();
    $codeSnippet = $this->getCodeSnippet($code, $lineNumber);
    $this->errorLogger->logParseError($filePath, $e, $codeSnippet);
    return [];
}
```

### Integration Checklist

- [ ] Replace silent error handling with ErrorLogger
- [ ] Add error checking after file processing  
- [ ] Implement error threshold monitoring
- [ ] Add error reporting to CLI output
- [ ] Configure appropriate error levels for your use case

## Troubleshooting

### Common Issues

**High Memory Usage from Error Storage**
```php
// Solution: Limit stored errors
$logger = new ErrorLogger(maxErrors: 50);
```

**Too Many Low-Priority Errors**  
```php
// Solution: Increase minimum severity
$logger = new ErrorLogger(minSeverity: ErrorSeverity::ERROR);
```

**Missing Error Context**
```php
// Ensure proper error context creation
$context = ErrorContext::syntaxError($file, $message, $line, $snippet);
$logger->log($context);
```

### Debug Mode

Enable debug output to see error handling in action:

```bash
export PHP_CODE_INTEL_DEBUG=1
php bin/php-code-intel find-usages "MyClass" --verbose
```

---

**The error handling system provides robust, production-ready error management for the PHP Code Intelligence Tool, ensuring reliable operation across diverse codebases.**