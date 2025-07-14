# PHP Code Intelligence Tool

A powerful, AST-based PHP symbol analysis tool designed for accurate refactoring and code navigation. Optimized for integration with Claude Code to provide precise symbol usage detection across large codebases.

## 🚀 Features

- **AST-based Analysis**: Uses nikic/php-parser for accurate, syntax-aware symbol detection
- **Advanced Confidence Scoring**: CERTAIN, PROBABLE, POSSIBLE, and DYNAMIC confidence levels
- **Modern PHP Support**: Nullsafe operators (`?->`), dynamic method calls, attributes, enums
- **Inheritance Analysis**: Full support for interfaces, traits, abstract classes, and complex hierarchies
- **Multiple Output Formats**: JSON, Table, and Claude-optimized formats
- **High Performance**: Memory-optimized processing for large codebases
- **Docker Ready**: Complete containerization for consistent development
- **PHAR Distribution**: Self-contained executable for easy deployment

## 📋 Requirements

- **PHP 8.2+** (Supports PHP 8.2, 8.3, 8.4)
- Extensions: json, mbstring, tokenizer
- Memory: 128MB+ recommended

> **Note**: PHP 8.0/8.1 are not supported due to modern dependency requirements (Symfony Console 6.4+, PHPUnit 10.5+)

## 🔧 Installation

### Option 1: Composer (Development)
```bash
git clone <repository>
cd php-code-intel
composer install
```

### Option 2: PHAR (Production)
```bash
# Build PHAR
./build/build.sh

# Use directly
./build/php-code-intel.phar --version
```

### Option 3: Docker
```bash
docker compose up -d app
docker compose exec app php bin/php-code-intel --version
```

## 🎯 Usage

### Find Symbol Usages
```bash
# Basic usage
php-code-intel find-usages "App\User"

# With path filtering
php-code-intel find-usages "App\User::getName" --path=src/

# Different output formats
php-code-intel find-usages "MyClass" --format=json
php-code-intel find-usages "MyClass" --format=table
php-code-intel find-usages "MyClass" --format=claude

# Confidence filtering
php-code-intel find-usages "Service::method" --confidence=CERTAIN

# Exclude paths
php-code-intel find-usages "MyClass" --exclude=vendor --exclude=tests
```

### Index Files
```bash
# Index directory
php-code-intel index src/

# Multiple paths with statistics
php-code-intel index src/ app/ --stats

# With exclusions
php-code-intel index . --exclude=vendor --exclude=node_modules
```

### System Information
```bash
# Basic version info
php-code-intel version

# Detailed system info
php-code-intel version -v
```

## 📊 Confidence Levels

| Level | Description | Examples |
|-------|-------------|----------|
| **CERTAIN** | Direct, unambiguous usage | `new Class()`, `Class::method()`, `Class::class` |
| **PROBABLE** | Type-hinted or chained usage | `function(Class $param)`, `$obj?->method()` |
| **POSSIBLE** | Dynamic but detectable | `new $className()`, `$obj->$method()` |
| **DYNAMIC** | Magic or runtime-determined | `call_user_func()`, `__call()`, `__get()` |

## 🎨 Output Formats

### Claude Format (Default)
Optimized for Claude Code integration with file:line references and context:
```
tests/fixtures/BasicSymbols/Classes.php:335
  $simple = new SimpleClass(); (confidence: CERTAIN)
  Context:
    333: 
    334: // Simple class usage
  > 335: $simple = new SimpleClass();
    336: echo $simple->getName() . "\n";
    337: 
```

### Table Format
Human-readable table view:
```
File           Line    Confidence  Code
Classes.php    335     CERTAIN     $simple = new SimpleClass();
Classes.php    387     CERTAIN     var_dump($simple instanceof SimpleClass);
```

### JSON Format
Machine-readable structured data:
```json
[
  {
    "file": "tests/fixtures/BasicSymbols/Classes.php",
    "line": 335,
    "code": "$simple = new SimpleClass();",
    "confidence": "CERTAIN",
    "type": "instantiation",
    "context": {
      "start": 333,
      "lines": ["", "// Simple class usage", "$simple = new SimpleClass();"]
    }
  }
]
```

## 🔍 Supported PHP Features

### Classes & Objects
- ✅ Class instantiation (`new Class()`)
- ✅ Static method calls (`Class::method()`)
- ✅ Class constants (`Class::CONSTANT`)
- ✅ instanceof checks
- ✅ Class references (`Class::class`)
- ✅ Anonymous classes

### Methods & Properties
- ✅ Method calls (`$obj->method()`)
- ✅ Property access (`$obj->property`)
- ✅ Nullsafe operators (`$obj?->method()`)
- ✅ Dynamic calls (`$obj->$method()`)
- ✅ Method chaining

### Modern PHP
- ✅ Attributes/Annotations
- ✅ Enums and backed enums
- ✅ Union and intersection types
- ✅ Match expressions
- ✅ Constructor property promotion

### Inheritance
- ✅ Interface implementations
- ✅ Trait usage and conflicts
- ✅ Abstract class extensions
- ✅ parent:: and self:: calls
- ✅ Complex inheritance hierarchies

## 🧪 Testing & Quality Assurance

### Running Tests
```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suites
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Integration/
```

### Static Analysis
```bash
# Run PHPStan analysis
composer phpstan

# Run all quality checks (PHPStan + Tests)
composer code-quality

# Using Makefile (with Docker)
make phpstan
make code-quality
```

### Test Coverage
- **43 tests, 156 assertions**
- **100% test success rate**
- **Comprehensive fixture coverage**

### Code Quality
- **PHPStan Level 6** - Static analysis for type safety
- **PSR-4 Autoloading** - Standard namespace conventions
- **Strict Types** - All files use `declare(strict_types=1)`

## 🏗️ Architecture

```
src/
├── Console/           # CLI commands and application
├── Finder/            # Core symbol finding logic
├── Index/             # File indexing and symbol storage
└── Parser/            # AST parsing and visitor patterns

tests/
├── Unit/              # Unit tests for individual components
├── Integration/       # End-to-end workflow tests
└── fixtures/          # Comprehensive PHP code samples
```

### Key Components

- **UsageFinder**: Coordinates the search process
- **UsageVisitor**: AST visitor for symbol detection
- **ConfidenceScorer**: Advanced pattern-based confidence assessment
- **SymbolIndex**: Efficient symbol storage and retrieval

## 🎛️ Configuration

### Environment Variables
- `PHP_CODE_INTEL_DEBUG=1`: Enable debug output
- `MEMORY_LIMIT`: Override PHP memory limit

### CLI Options
- `--path, -p`: Search paths (files or directories)
- `--format, -f`: Output format (json|table|claude)
- `--exclude, -e`: Exclude paths from search
- `--confidence, -c`: Minimum confidence level
- `--verbose, -v`: Verbose output
- `--help, -h`: Command help

## 🚀 Integration with Claude Code

This tool is specifically designed for Claude Code integration:

1. **Optimized Output**: Claude format provides file:line references with context
2. **Accurate Results**: High-confidence symbol detection reduces false positives
3. **Performance**: Fast indexing and search for real-time usage
4. **Comprehensive**: Covers all PHP language features and patterns

### Example Integration
```bash
# Find all usages of a class for refactoring
php-code-intel find-usages "App\Models\User" --format=claude
```

## 📈 Performance

- **Indexing**: ~1000 files/second on modern hardware
- **Search**: Sub-second response for typical codebases
- **Memory**: Optimized for large projects (500MB+ codebases)
- **Caching**: File hash-based change detection

## 🐛 Troubleshooting

### Common Issues

**PHAR Creation Fails**
```bash
# Enable PHAR writing
php -d phar.readonly=0 build/build-phar.php
```

**Memory Limit Exceeded**
```bash
# Increase memory limit
php -d memory_limit=512M bin/php-code-intel find-usages "MyClass"
```

**Missing Extensions**
```bash
# Check required extensions
php -m | grep -E "(json|mbstring|tokenizer)"
```

## 📚 Documentation

### For Developers
- **[CLAUDE.md](CLAUDE.md)** - Instructions for Claude integration
- **[PROJECT_TRACKER.md](PROJECT_TRACKER.md)** - Development progress
- **[IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)** - Technical roadmap
- **[TDD_TEST_PLAN.md](TDD_TEST_PLAN.md)** - Test specifications

### Technical References
- **[docs/SYMBOL_TYPES.md](docs/SYMBOL_TYPES.md)** - PHP symbol reference
- **[docs/ALGORITHMS.md](docs/ALGORITHMS.md)** - Core algorithms

## 📄 License

MIT License - see LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## 🔗 Related Projects

- [nikic/php-parser](https://github.com/nikic/PHP-Parser) - PHP AST parsing
- [symfony/console](https://symfony.com/doc/current/console.html) - CLI framework
- [Claude Code](https://claude.ai/code) - AI-powered code assistant

---

**Built with ❤️ for the PHP community and Claude Code integration**