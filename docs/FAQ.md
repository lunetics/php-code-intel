# Frequently Asked Questions (FAQ)

## Overview

This FAQ addresses common questions, troubleshooting scenarios, and best practices for using the PHP Code Intelligence Tool.

## Installation & Setup

### Q: Can I use this tool without installing PHP locally?

**A:** Yes! Use the Docker runtime container approach:

```bash
# One-time setup
make build-runtime

# Use with any project
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  find-usages "App\\User" --path=src/

# Or setup shell function for convenience
./scripts/runtime-setup.sh
php-code-intel find-usages "App\\User" --path=src/
```

This approach works on any system with Docker, regardless of local PHP installation.

### Q: What PHP versions are supported?

**A:** The tool supports PHP 8.2, 8.3, and 8.4. PHP 8.0/8.1 are not supported due to modern dependency requirements.

```bash
# Check your PHP version
php --version

# Verify compatibility
php -r "echo PHP_VERSION_ID >= 80200 ? 'Compatible' : 'Incompatible';"
```

### Q: Why do I get "PHP 8.0 not supported" errors?

**A:** The tool requires PHP 8.2+ for several reasons:

- **Symfony Console 6.4+** requires PHP 8.2
- **PHPUnit 10.5+** requires PHP 8.2
- **Modern type system** features used throughout
- **Enum support** is critical for error handling

**Solution:** Upgrade to PHP 8.2 or newer.

### Q: How do I install without Docker?

**A:** Direct installation via Composer:

```bash
# Clone repository
git clone https://github.com/lunetics/php-code-intel.git
cd php-code-intel

# Install dependencies
composer install

# Verify installation
php bin/php-code-intel --version
```

### Q: The PHAR build fails with "phar.readonly" error

**A:** PHP has PHAR writing disabled by default:

```bash
# Check current setting
php -i | grep phar.readonly

# Enable PHAR writing temporarily
php -d phar.readonly=0 build/build-phar.php

# Or set in php.ini
echo "phar.readonly = Off" >> /etc/php/8.4/cli/php.ini
```

### Q: How do I build the Docker runtime container?

**A:** Use the provided Make commands:

```bash
# Build production runtime container
make build-runtime

# Build development container with debugging tools
make build-runtime-dev

# Test the container
make test-runtime

# Clean up containers
make clean-runtime
```

### Q: Can I use the runtime container in CI/CD pipelines?

**A:** Yes! Here's a GitHub Actions example:

```yaml
- name: Run PHP Code Intelligence Analysis
  run: |
    make build-runtime
    docker run --rm -v ${{ github.workspace }}:/workspace php-code-intel:runtime \
      find-usages "App\\Models\\User" --path=src/ --format=json > analysis.json
```

### Q: How do I customize the Docker runtime container?

**A:** You can extend the base runtime container:

```dockerfile
FROM php-code-intel:runtime

# Add custom PHP extensions
RUN docker-php-ext-install pdo_mysql

# Add custom tools
RUN apk add --no-cache jq

# Custom entrypoint
COPY custom-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["custom-entrypoint.sh"]
```

## Usage & Configuration

### Q: How do I find all usages of a class including inheritance?

**A:** Use multiple searches to cover inheritance patterns:

```bash
# Direct class usage
php-code-intel find-usages "App\\Models\\User" --format=json

# Check for inheritance patterns
php-code-intel find-usages "User" --format=json  # Unqualified name
php-code-intel find-usages "extends User" --format=json  # Inheritance
php-code-intel find-usages "implements UserInterface" --format=json  # Interfaces
```

### Q: Why are some usages marked as "DYNAMIC" confidence?

**A:** Dynamic confidence indicates usage that's determined at runtime:

```php
// DYNAMIC examples
$obj->__call('methodName', []);      // Magic methods
call_user_func([$obj, 'method']);    // Reflection calls
$method = 'getName'; $obj->$method(); // Variable methods
```

**These require manual review** as they can't be statically analyzed with certainty.

### Q: How do I exclude vendor/node_modules from analysis?

**A:** Use the `--exclude` option:

```bash
# Single exclusion
php-code-intel find-usages "MyClass" --exclude=vendor

# Multiple exclusions
php-code-intel find-usages "MyClass" --exclude=vendor --exclude=node_modules --exclude=cache

# Pattern exclusions
php-code-intel find-usages "MyClass" --exclude="**/tests/**" --exclude="**/cache/**"
```

### Q: What's the difference between output formats?

**A:** Three formats are available:

| Format | Best For | Example Output |
|--------|----------|----------------|
| `claude` | Claude Code integration | `file.php:42 \n  $user = new User(); (CERTAIN)` |
| `json` | Programmatic processing | `[{"file": "...", "line": 42, ...}]` |
| `table` | Human reading | ASCII table with columns |

```bash
# Choose format based on needs
php-code-intel find-usages "User" --format=claude   # Default
php-code-intel find-usages "User" --format=json     # API integration  
php-code-intel find-usages "User" --format=table    # Terminal viewing
```

## Performance & Memory

### Q: The tool runs out of memory on large projects

**A:** Several strategies can help:

```bash
# 1. Increase PHP memory limit
php -d memory_limit=512M bin/php-code-intel find-usages "MyClass"

# 2. Use path filtering to reduce scope
php-code-intel find-usages "MyClass" --path=src/ --exclude=vendor

# 3. Process in smaller batches
find src/ -name "*.php" | head -100 | xargs php-code-intel index
```

For programmatic usage:

```php
// Process files in batches
$files = glob('src/**/*.php', GLOB_BRACE);
$batches = array_chunk($files, 50);

foreach ($batches as $batch) {
    $index = new SymbolIndex();
    foreach ($batch as $file) {
        $index->addFile($file);
    }
    
    $finder = new UsageFinder($index);
    $usages = $finder->find('MyClass');
    
    // Process usages
    unset($index, $finder); // Free memory
    gc_collect_cycles();
}
```

### Q: How can I improve search performance?

**A:** Performance optimization tips:

1. **Use specific paths**: `--path=src/` instead of entire project
2. **Filter by confidence**: `--confidence=CERTAIN` for exact matches only
3. **Index once, search multiple**: Build index first, then search
4. **Use PHAR**: Slightly faster startup than Composer version

```bash
# Efficient workflow
php-code-intel index src/ app/          # Index once
php-code-intel find-usages "Class1"     # Fast searches
php-code-intel find-usages "Class2"     # Reuse index
```

### Q: Why is indexing slow for my project?

**A:** Common causes and solutions:

1. **Large files**: Break up monolithic classes
2. **Complex inheritance**: Deep hierarchies slow analysis
3. **Generated code**: Exclude auto-generated files
4. **Symlinks**: May cause double-processing

```bash
# Profile indexing performance
time php-code-intel index src/ --verbose

# Exclude problematic patterns
php-code-intel index src/ --exclude="**/generated/**" --exclude="**/cache/**"
```

## Error Handling & Troubleshooting

### Q: I get "Parse error" for valid PHP files

**A:** This usually indicates PHP version compatibility issues:

```bash
# Check what PHP version the parser uses
php -r "use PhpParser\ParserFactory; 
$factory = new ParserFactory();
$parser = $factory->create(ParserFactory::PREFER_PHP7);
echo 'Parser created successfully';"

# Try parsing the problematic file directly
php -l /path/to/problematic/file.php
```

Common causes:
- **PHP 8+ syntax** in files (enums, attributes, etc.)
- **Syntax errors** in source files
- **Encoding issues** (non-UTF-8 files)

### Q: How do I handle files with syntax errors?

**A:** The tool logs errors instead of failing:

```php
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Finder\UsageFinder;

$finder = new UsageFinder($index);
$usages = $finder->find('MyClass');

// Check for errors after processing
$errorLogger = $finder->getErrorLogger();
$summary = $errorLogger->getErrorSummary();

if ($summary['total'] > 0) {
    echo "Encountered {$summary['total']} errors:\n";
    foreach ($summary['byCategory'] as $category => $count) {
        echo "  {$category}: {$count}\n";
    }
}
```

### Q: What does "Class not found" mean?

**A:** Several possibilities:

1. **Autoloading issue**: Class not in indexed files
2. **Namespace mismatch**: Incorrect fully qualified name
3. **Case sensitivity**: PHP is case-insensitive, but names must match exactly

```bash
# Debug steps
php-code-intel index src/ --verbose    # See what's being indexed
php-code-intel find-usages "\\App\\User"  # Try with leading backslash
php-code-intel find-usages "App\\Models\\User"  # Try full namespace
```

### Q: How do I report parsing errors?

**A:** Get detailed error information:

```php
// Enable debug mode
$errorLogger = new ErrorLogger();
$finder = new UsageFinder($index, $errorLogger);

// After processing
foreach ($errorLogger->getErrors() as $error) {
    if ($error->category === ErrorCategory::PARSER_ERROR) {
        echo "Parse error in {$error->filePath}:{$error->lineNumber}\n";
        echo "Message: {$error->message}\n";
        if ($error->codeSnippet) {
            echo "Code:\n{$error->codeSnippet}\n";
        }
    }
}
```

## Integration & Advanced Usage

### Q: How do I integrate with my IDE?

**A:** Create a simple wrapper script:

```php
#!/usr/bin/env php
<?php
// ide-wrapper.php

require_once 'vendor/autoload.php';

$request = json_decode(file_get_contents('php://input'), true);
$symbol = $request['symbol'] ?? '';
$projectPath = $request['project_path'] ?? getcwd();

if (!$symbol) {
    echo json_encode(['error' => 'Symbol required']);
    exit(1);
}

// Run analysis
exec(sprintf(
    'php bin/php-code-intel find-usages %s --path=%s --format=json',
    escapeshellarg($symbol),
    escapeshellarg($projectPath)
), $output, $returnCode);

if ($returnCode === 0) {
    echo implode("\n", $output);
} else {
    echo json_encode(['error' => 'Analysis failed']);
}
```

### Q: Can I use this in CI/CD pipelines?

**A:** Yes! Example GitHub Actions workflow:

```yaml
# .github/workflows/symbol-analysis.yml
name: Symbol Usage Analysis

on: [push, pull_request]

jobs:
  analyze:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          
      - name: Install dependencies
        run: composer install
        
      - name: Analyze critical symbols
        run: |
          symbols=("App\\Models\\User" "App\\Services\\UserService")
          for symbol in "${symbols[@]}"; do
            echo "Analyzing: $symbol"
            php bin/php-code-intel find-usages "$symbol" --format=json > "analysis-${symbol//\\\\/-}.json"
          done
          
      - name: Upload analysis results
        uses: actions/upload-artifact@v3
        with:
          name: symbol-analysis
          path: analysis-*.json
```

### Q: How do I create custom confidence scorers?

**A:** Extend the ConfidenceScorer class:

```php
use CodeIntel\Finder\ConfidenceScorer;

class CustomConfidenceScorer extends ConfidenceScorer
{
    public function score(string $code): string
    {
        // Add custom patterns
        if (preg_match('/MyFramework::create/', $code)) {
            return 'CERTAIN';  // Framework-specific patterns
        }
        
        if (preg_match('/\$di->get\(.*::class\)/', $code)) {
            return 'PROBABLE'; // Dependency injection
        }
        
        // Fall back to parent logic
        return parent::score($code);
    }
}

// Use custom scorer
$finder = new UsageFinder($index);
$reflection = new ReflectionClass($finder);
$scorerProperty = $reflection->getProperty('scorer');
$scorerProperty->setAccessible(true);
$scorerProperty->setValue($finder, new CustomConfidenceScorer());
```

## Best Practices

### Q: What's the best workflow for refactoring?

**A:** Follow this systematic approach:

1. **Index project** thoroughly
2. **Find all usages** of target symbol
3. **Review confidence levels** - focus on CERTAIN/PROBABLE first
4. **Handle DYNAMIC usages** manually
5. **Test after changes**

```bash
# 1. Complete project index
php-code-intel index . --exclude=vendor --exclude=tests

# 2. Find all usages
php-code-intel find-usages "OldClass" --format=json > usages.json

# 3. Analyze results
cat usages.json | jq '.[] | select(.confidence == "DYNAMIC")'

# 4. Safe replacements first
cat usages.json | jq '.[] | select(.confidence == "CERTAIN")' | \
  jq -r '"\(.file):\(.line)"' > safe-replacements.txt

# 5. Verify no critical patterns
grep -E "(call_user_func|__call|reflection)" usages.json
```

### Q: How do I handle large monorepos?

**A:** Use path-based analysis:

```bash
# Analyze by service/module
php-code-intel find-usages "SharedClass" --path=services/user/
php-code-intel find-usages "SharedClass" --path=services/billing/
php-code-intel find-usages "SharedClass" --path=services/auth/

# Combine results programmatically
php scripts/merge-analysis-results.php
```

### Q: What symbols should I monitor in CI?

**A:** Focus on critical architectural components:

```bash
# Core domain models
php-code-intel find-usages "App\\Models\\User"
php-code-intel find-usages "App\\Models\\Order"

# Critical services
php-code-intel find-usages "PaymentService"
php-code-intel find-usages "AuthenticationService"

# Public APIs
php-code-intel find-usages "ApiController"
php-code-intel find-usages "PublicInterface"
```

## Debugging & Diagnostics

### Q: How do I enable verbose output?

**A:** Use the `--verbose` flag:

```bash
php-code-intel find-usages "MyClass" --verbose
```

For programmatic debugging:

```php
// Set environment variable
putenv('PHP_CODE_INTEL_DEBUG=1');

// Or enable in error logger
$errorLogger = new ErrorLogger(
    maxErrors: 1000,
    minSeverity: ErrorSeverity::INFO  // Log everything
);
```

### Q: How do I profile performance?

**A:** Use built-in timing and memory reporting:

```bash
# Time the operation
time php-code-intel find-usages "LargeClass" --path=src/

# Memory usage
php -d memory_limit=1G bin/php-code-intel find-usages "LargeClass" --verbose

# Detailed profiling with Xdebug
php -d xdebug.mode=profile bin/php-code-intel find-usages "LargeClass"
```

### Q: The tool gives different results each time

**A:** This suggests file system caching issues:

```bash
# Clear any caches
rm -rf var/cache/* tmp/* 

# Use absolute paths
php-code-intel find-usages "MyClass" --path=/absolute/path/to/src

# Check for symlinks
find . -type l -ls
```

## Migration & Compatibility

### Q: How do I migrate from older versions?

**A:** Check for breaking changes:

```bash
# Version 1.x to 2.x
# - Error handling API changed
# - PHPStan level increased
# - Some method signatures changed

# Check your integration points
grep -r "UsageFinder" your-integration-code/
grep -r "ErrorLogger" your-integration-code/
```

### Q: Can I use this with legacy PHP codebases?

**A:** The tool can **analyze** legacy code but **requires** PHP 8.2+ to **run**:

```bash
# Analyze PHP 5.6 code with PHP 8.4 tool
php8.4 bin/php-code-intel find-usages "LegacyClass" --path=legacy-src/
```

However, some modern PHP features won't be recognized in legacy code.

---

## Getting More Help

If your question isn't answered here:

1. **Search existing issues**: https://github.com/lunetics/php-code-intel/issues
2. **Create a new issue**: Include code samples and error messages
3. **Check documentation**: See other docs/ files for detailed information
4. **Community discussions**: Join GitHub Discussions for general questions

**For bug reports, please include:**
- PHP version (`php --version`)
- Tool version (`php bin/php-code-intel --version`)
- Sample code that reproduces the issue
- Error messages and stack traces
- Project structure (if relevant)

---

**This FAQ is continuously updated based on user feedback and common issues. If you have suggestions for additional questions, please open an issue!**