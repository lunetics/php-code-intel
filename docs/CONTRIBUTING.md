# Contributing Guidelines

## Overview

Thank you for your interest in contributing to the PHP Code Intelligence Tool! This guide will help you get started with development, testing, and submitting contributions.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Environment](#development-environment)
- [Code Standards](#code-standards)
- [Testing Requirements](#testing-requirements)
- [Contribution Workflow](#contribution-workflow)
- [Release Process](#release-process)
- [Performance Guidelines](#performance-guidelines)
- [Documentation Standards](#documentation-standards)

## Getting Started

### Prerequisites

- **PHP 8.2+** (8.2, 8.3, or 8.4)
- **Composer** for dependency management
- **Git** for version control
- **Docker** (optional, for isolated development)

### Required Extensions

```bash
# Check required extensions
php -m | grep -E "(json|mbstring|tokenizer)"
```

### Fork and Clone

```bash
# Fork the repository on GitHub, then clone your fork
git clone https://github.com/YOUR-USERNAME/php-code-intel.git
cd php-code-intel

# Add upstream remote
git remote add upstream https://github.com/lunetics/php-code-intel.git
```

## Development Environment

### Local Setup

```bash
# Install dependencies
composer install

# Verify installation
php bin/php-code-intel --version

# Run tests to ensure everything works
composer test
```

### Docker Setup (Recommended)

```bash
# Start development container
docker compose up -d

# Enter container for development
docker compose exec app bash

# Inside container
composer install
php bin/php-code-intel --version
composer test
```

### Multi-PHP Testing

```bash
# Test across all supported PHP versions
make test-all

# Test specific version
make test-84  # PHP 8.4
make test-83  # PHP 8.3
make test-82  # PHP 8.2
```

## Code Standards

### PHP Standards

We maintain **PHPStan Level 9** compliance (maximum strictness):

```bash
# Run static analysis
composer phpstan

# Fix any issues before submitting
vendor/bin/phpstan analyse --level=9
```

### Code Style Requirements

1. **Strict Types**: All files must use `declare(strict_types=1)`
2. **PSR-4 Autoloading**: Follow namespace conventions
3. **Type Declarations**: Use type hints for all parameters and return types
4. **Readonly Classes**: Use `readonly` where appropriate
5. **Modern PHP Features**: Utilize PHP 8.2+ features (enums, union types, etc.)

### Example Class Structure

```php
<?php

declare(strict_types=1);

namespace CodeIntel\Your\Namespace;

use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorCategory;

/**
 * Brief description of the class purpose
 * 
 * Longer description if needed, explaining the main responsibilities
 * and usage patterns.
 */
final readonly class YourClass
{
    public function __construct(
        private string $requiredParam,
        private ?ErrorLogger $errorLogger = null
    ) {}
    
    /**
     * Brief method description
     * 
     * @param string $input Input parameter description
     * @return array<string, mixed> Return value description
     * @throws \InvalidArgumentException When input is invalid
     */
    public function processData(string $input): array
    {
        if (empty($input)) {
            throw new \InvalidArgumentException('Input cannot be empty');
        }
        
        // Implementation
        return [];
    }
}
```

### Error Handling Standards

All new code must use the comprehensive error handling system:

```php
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorCategory;
use CodeIntel\Error\ErrorContext;

class YourProcessor
{
    public function __construct(
        private ErrorLogger $errorLogger
    ) {}
    
    public function processFile(string $filePath): array
    {
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                $this->errorLogger->logIoError($filePath, 'Failed to read file');
                return [];
            }
            
            return $this->parseContent($content);
            
        } catch (\PhpParser\Error $e) {
            $this->errorLogger->logParseError($filePath, $e);
            return [];
        } catch (\Throwable $e) {
            $this->errorLogger->logIoError($filePath, 'Unexpected error: ' . $e->getMessage(), $e);
            return [];
        }
    }
}
```

## Testing Requirements

### Test Coverage

- **All new classes** must have unit tests
- **Critical functions** must have comprehensive test coverage
- **Error scenarios** must be tested
- **Edge cases** should be covered

### Test Structure

```php
<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit\Your\Namespace;

use CodeIntel\Your\Namespace\YourClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for YourClass
 */
class YourClassTest extends TestCase
{
    private YourClass $subject;
    
    protected function setUp(): void
    {
        $this->subject = new YourClass('test-param');
    }
    
    public function test_constructor_sets_properties(): void
    {
        $instance = new YourClass('param-value');
        
        // Use reflection if needed to test private properties
        $reflection = new \ReflectionClass($instance);
        $property = $reflection->getProperty('requiredParam');
        $property->setAccessible(true);
        
        $this->assertEquals('param-value', $property->getValue($instance));
    }
    
    public function test_process_data_returns_expected_result(): void
    {
        $result = $this->subject->processData('test-input');
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
    
    public function test_process_data_throws_exception_for_empty_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input cannot be empty');
        
        $this->subject->processData('');
    }
    
    /**
     * @dataProvider invalidInputProvider
     */
    public function test_process_data_handles_invalid_input(string $input, string $expectedError): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedError);
        
        $this->subject->processData($input);
    }
    
    /** @return array<string, array{0: string, 1: string}> */
    public static function invalidInputProvider(): array
    {
        return [
            'empty string' => ['', 'Input cannot be empty'],
            'whitespace only' => ['   ', 'Input cannot be empty'],
        ];
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/YourClassTest.php

# Run with coverage
composer test-coverage

# Run tests for specific group
vendor/bin/phpunit --group=error-handling
```

### Test Fixtures

Add realistic test fixtures for complex scenarios:

```php
// tests/fixtures/YourFeature/TestCase.php
<?php

namespace TestFixtures\YourFeature;

class ComplexTestCase
{
    public function processData(): void
    {
        // Complex code that exercises your feature
    }
}
```

## Contribution Workflow

### 1. Issue Creation

Before starting work:

1. **Check existing issues** to avoid duplication
2. **Create an issue** describing the problem or feature
3. **Get approval** from maintainers for significant changes
4. **Self-assign** the issue when you start work

### 2. Branch Naming

Use descriptive branch names:

```bash
# Feature branches
git checkout -b feature/enhanced-error-reporting
git checkout -b feature/performance-optimization

# Bug fix branches  
git checkout -b fix/memory-leak-in-parser
git checkout -b fix/confidence-scoring-edge-case

# Documentation branches
git checkout -b docs/api-reference-update
```

### 3. Development Process

```bash
# Start from latest main
git checkout main
git pull upstream main

# Create feature branch
git checkout -b feature/your-feature

# Make your changes, commit frequently
git add .
git commit -m "Add initial implementation of feature"

# Keep your branch updated
git fetch upstream
git rebase upstream/main

# Push to your fork
git push origin feature/your-feature
```

### 4. Pull Request Guidelines

#### PR Title Format

```
[Type] Brief description of changes

Examples:
[Feature] Add comprehensive error logging system
[Fix] Resolve memory leak in symbol indexing
[Docs] Update API reference documentation
[Refactor] Improve confidence scoring algorithm
[Test] Add unit tests for error handling
```

#### PR Description Template

```markdown
## Summary
Brief description of what this PR does.

## Changes Made
- [ ] Added new ErrorLogger class with PSR-3 compatibility
- [ ] Enhanced UsageFinder with error context
- [ ] Added 30 comprehensive unit tests
- [ ] Updated documentation

## Testing
- [ ] All existing tests pass
- [ ] New tests added for new functionality
- [ ] PHPStan Level 9 analysis passes
- [ ] Manual testing completed

## Breaking Changes
List any breaking changes and migration steps.

## Related Issues
Fixes #123
Relates to #456

## Checklist
- [ ] Code follows project standards
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] PHPStan passes
- [ ] No merge conflicts
```

### 5. Code Review Process

#### As an Author

- **Self-review** your changes before requesting review
- **Add tests** for all new functionality
- **Update documentation** for API changes
- **Respond promptly** to reviewer feedback
- **Rebase and force-push** to keep history clean

#### As a Reviewer

- **Focus on** logic, design, and maintainability
- **Check for** proper error handling and testing
- **Verify** PHPStan compliance
- **Test locally** for complex changes
- **Be constructive** in feedback

## Performance Guidelines

### Memory Efficiency

```php
// Good: Process in batches
foreach (array_chunk($largeArray, 100) as $batch) {
    $this->processBatch($batch);
    gc_collect_cycles(); // Force garbage collection
}

// Bad: Process everything at once
foreach ($largeArray as $item) {
    $this->processItem($item);
}
```

### Time Complexity

- **Prefer O(1) lookups** over O(n) searches
- **Use generators** for large datasets
- **Cache results** when appropriate
- **Profile performance** for critical paths

### Benchmark Requirements

For performance-critical changes:

```bash
# Run benchmarks
make benchmark

# Compare with baseline
php benchmark/compare.php baseline.json current.json
```

## Documentation Standards

### API Documentation

All public methods require comprehensive documentation:

```php
/**
 * Find all usages of a symbol across the indexed codebase
 * 
 * This method searches through all indexed files to locate instances
 * where the specified symbol is used. It returns detailed information
 * about each usage including file location, code context, and confidence level.
 * 
 * @param string $symbolName Fully qualified symbol name (e.g., "App\\User", "User::getName")
 * @return array<array{file: string, line: int, code: string, confidence: string, type: string, context: array{start: int, end: int, lines: array<string>}}> Array of usage information
 * @throws \InvalidArgumentException When symbol name is empty or invalid
 * 
 * @example
 * ```php
 * $usages = $finder->find('App\\Models\\User');
 * foreach ($usages as $usage) {
 *     echo "{$usage['file']}:{$usage['line']} - {$usage['confidence']}\n";
 * }
 * ```
 */
public function find(string $symbolName): array
```

### README Updates

For feature additions, update relevant sections:

- Feature list
- Usage examples  
- Configuration options
- Performance metrics

### Documentation Files

Create/update documentation files as needed:

- `docs/API_REFERENCE.md` - For API changes
- `docs/ERROR_HANDLING.md` - For error handling features
- `docs/ADVANCED_USAGE.md` - For complex usage scenarios

## Release Process

### Version Numbering

We use [Semantic Versioning](https://semver.org/):

- **MAJOR** - Breaking changes
- **MINOR** - New features, backwards compatible
- **PATCH** - Bug fixes, backwards compatible

### Pre-Release Checklist

Before creating a release:

```bash
# 1. Run full test suite
make test-all

# 2. Verify PHPStan compliance
composer phpstan

# 3. Update version numbers
# - composer.json
# - Application.php
# - README.md

# 4. Update CHANGELOG.md

# 5. Create release tag
git tag -a v2.1.0 -m "Release version 2.1.0"
git push upstream --tags
```

### Release Notes Format

```markdown
## [2.1.0] - 2024-01-15

### Added
- Comprehensive error logging system with PSR-3 compatibility
- 30 new unit tests for error handling scenarios
- Enhanced confidence scoring with new patterns

### Changed
- Upgraded PHPStan analysis from Level 6 to Level 9
- Improved memory efficiency in large file processing

### Fixed
- Memory leak in symbol indexing for large projects
- Edge case in confidence scoring for dynamic calls

### Breaking Changes
- ErrorLogger constructor signature changed (backwards incompatible)
- Removed deprecated findSymbol() method
```

## Getting Help

### Communication Channels

- **GitHub Issues** - Bug reports and feature requests
- **Discussions** - General questions and ideas
- **Code Review** - In pull request comments

### Development Resources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PSR Standards](https://www.php-fig.org/psr/)
- [nikic/php-parser Documentation](https://github.com/nikic/PHP-Parser/blob/master/doc/0_Introduction.markdown)

### Common Issues

**PHPStan Errors**
```bash
# Clear cache and try again
vendor/bin/phpstan clear-result-cache
vendor/bin/phpstan analyse
```

**Test Failures**
```bash
# Run tests with verbose output
vendor/bin/phpunit --verbose

# Check for missing fixtures
ls tests/fixtures/
```

**Memory Issues in Tests**
```bash
# Increase memory limit
php -d memory_limit=512M vendor/bin/phpunit
```

## Recognition

Contributors will be:
- **Listed** in CONTRIBUTORS.md
- **Mentioned** in release notes for significant contributions
- **Thanked** in commit messages

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to the PHP Code Intelligence Tool! Your efforts help improve PHP development tools for the entire community.**