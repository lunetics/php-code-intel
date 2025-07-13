# PHP Code Intelligence Tool for Claude Code

## Project Documentation Navigation

### 📋 Main Documentation Files

1. **[CLAUDE.md](CLAUDE.md)** - Start here! Main instructions for Claude
2. **[PROJECT_TRACKER.md](PROJECT_TRACKER.md)** - Current implementation status
3. **[IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)** - Detailed 4-week plan
4. **[RESEARCH_FINDINGS.md](RESEARCH_FINDINGS.md)** - Analysis of PHPStan, Rector, Psalm, IDEs
5. **[TDD_TEST_PLAN.md](TDD_TEST_PLAN.md)** - Comprehensive test specifications
6. **[TECHNICAL_DECISIONS.md](TECHNICAL_DECISIONS.md)** - Architecture choices explained

### 📁 Technical Documentation (docs/)

7. **[docs/SYMBOL_TYPES.md](docs/SYMBOL_TYPES.md)** - All PHP symbols reference
8. **[docs/ALGORITHMS.md](docs/ALGORITHMS.md)** - Core algorithms with pseudocode

## Quick Start for New Claude Session

1. Read **CLAUDE.md** first for project context
2. Check **PROJECT_TRACKER.md** for current status
3. Review **IMPLEMENTATION_PLAN.md** for next steps
4. Use other docs as reference when needed

## Project Overview

A standalone PHP tool that provides intelligent code analysis and symbol usage finding, specifically optimized for Claude Code workflows. 

### Key Features
- Find all usages of any PHP symbol (classes, methods, properties, etc.)
- Confidence scoring for each usage (CERTAIN, PROBABLE, POSSIBLE, DYNAMIC)
- Optimized JSON output for Claude Code
- Distributed as a single PHAR file
- Works with any PHP 8.0+ project

### Problem It Solves
When Claude Code refactors PHP code, it often misses symbol usages in other files. This tool provides IDE-level "Find Usages" functionality, ensuring complete and accurate refactoring.

### Technical Approach
- Uses nikic/php-parser (industry standard)
- Two-phase search algorithm (fast filter + semantic validation)
- Aggressive caching for performance
- Comprehensive test coverage with TDD

## Current Status

**Phase**: Documentation & Planning
**Progress**: 90% complete

See [PROJECT_TRACKER.md](PROJECT_TRACKER.md) for detailed status.

## For Developers

This project is being developed with:
- Test-Driven Development (TDD)
- PHP 8.0+ features
- Docker for consistent environment
- PHAR distribution for easy deployment

---

*This tool gives Claude Code professional-grade PHP intelligence for accurate refactoring.*