# CLAUDE.md - PHP Code Intelligence Tool Instructions

## Project Overview

You are working on **PHP Code Intelligence Tool** - a standalone PHP tool that helps Claude Code find all occurrences of PHP symbols (classes, methods, properties, functions, etc.) for accurate refactoring.

### Primary Goal
Create a tool that Claude Code can use to find ALL usages of any PHP symbol in a codebase, with confidence scoring, to enable safe and complete refactoring operations.

### Key Requirements
1. **Accuracy**: Find ALL occurrences, including dynamic usages
2. **Performance**: Fast enough for real-time use during coding
3. **Standalone**: Distributed as a single PHAR file
4. **Claude-Optimized**: Output format designed for AI consumption

## Quick Context

This tool addresses a critical gap: When Claude Code refactors PHP code, it often misses usages in other files. This tool provides IDE-level "Find Usages" functionality optimized for Claude.

## Project Status

**Current Phase**: Planning & Documentation (Phase 0)
**Next Step**: Check PROJECT_TRACKER.md for detailed status

## Important Files

1. **PROJECT_TRACKER.md** - Current implementation status
2. **RESEARCH_FINDINGS.md** - Analysis of PHPStan, Rector, Psalm, and IDEs
3. **IMPLEMENTATION_PLAN.md** - Detailed 4-week plan
4. **TDD_TEST_PLAN.md** - Test specifications
5. **TECHNICAL_DECISIONS.md** - Architecture choices
6. **current_plan.md** - Original planning document

## Key Technical Decisions

1. **Parser**: nikic/php-parser (industry standard)
2. **Algorithm**: Two-phase (fast filter + semantic validation)
3. **Distribution**: PHAR file
4. **PHP Version**: 8.0+ (for modern features)
5. **Testing**: TDD with comprehensive fixtures

## Architecture Summary

```
Input: Symbol name (e.g., "UserService::updateProfile")
  ↓
Phase 1: Fast Filtering
- Text/trigram index lookup
- Returns candidate files
  ↓
Phase 2: Semantic Analysis
- Parse AST with php-parser
- Resolve symbols
- Score confidence
  ↓
Output: JSON with usages, context, and suggestions
```

## Development Workflow

### For New Claude Sessions

1. Read this file first
2. Check PROJECT_TRACKER.md for current status
3. Review IMPLEMENTATION_PLAN.md for next tasks
4. Run tests to verify current state
5. Continue from documented checkpoint

### Key Commands

```bash
# Run in Docker PHP container
docker run -v $(pwd):/app php:8.3-cli bash

# Inside container
composer install
./vendor/bin/phpunit
php bin/code-intel find "ClassName"

# Build PHAR (when ready)
php build/create-phar.php
```

## Output Format for Claude

The tool outputs JSON optimized for Claude Code:

```json
{
  "symbol": "Full\\Path\\To\\Symbol",
  "usages": [
    {
      "file": "path/to/file.php",
      "line": 42,
      "confidence": "CERTAIN|PROBABLE|POSSIBLE|DYNAMIC",
      "code": "actual code line",
      "context": "surrounding lines"
    }
  ],
  "suggestions": ["refactoring hints"],
  "warnings": ["potential issues"]
}
```

## Testing Philosophy

- **TDD First**: Write tests before implementation
- **Comprehensive Fixtures**: Cover ALL PHP features
- **Real-World Scenarios**: Include framework patterns
- **Performance Benchmarks**: Ensure tool is fast

## Important Context

### Why This Tool Exists
Claude Code lacks awareness of symbol usages across files. This tool provides that awareness, enabling:
- Safe method renaming
- Complete class refactoring  
- Finding all implementations
- Impact analysis

### Design Principles
1. **Accuracy over Speed** (but still fast)
2. **Explicit over Magic** (clear confidence levels)
3. **Claude-First** (output for AI, not humans)
4. **Zero Setup** (single PHAR, works anywhere)

## Next Steps

1. Check PROJECT_TRACKER.md
2. Implement next pending phase
3. Run tests frequently
4. Update tracker after progress

## Common Tasks

### Finding a Symbol
```bash
php bin/code-intel find "UserService"
php bin/code-intel find "UserService::updateProfile"
php bin/code-intel find "SOME_CONSTANT"
```

### Running Tests
```bash
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Integration/
```

### Building PHAR
```bash
composer install --no-dev --optimize-autoloader
php build/create-phar.php
```

## Remember

- This tool helps Claude Code refactor PHP accurately
- Always update PROJECT_TRACKER.md after progress
- Keep tests passing
- Document any architectural changes
- Performance matters (check benchmarks)

---

*Last Updated: [Auto-update when saving]*
*Project Started: 2024*
*Goal: Give Claude Code IDE-level PHP intelligence*