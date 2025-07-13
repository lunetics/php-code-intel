# Technical Decisions - PHP Code Intelligence Tool

## Overview

This document captures all major technical decisions made for the PHP Code Intelligence Tool, including rationale, alternatives considered, and tradeoffs.

## Core Decisions

### 1. Parser: nikic/php-parser

**Decision**: Use nikic/php-parser for all PHP parsing needs.

**Rationale**:
- Industry standard - used by PHPStan, Rector, Psalm
- Maintained by PHP core team member (Nikita Popov)
- Handles 100% of PHP syntax correctly (PHP 5.2 - 8.3)
- Stable API with excellent documentation
- Rich visitor pattern for AST traversal
- Proven in production at scale

**Alternatives Considered**:
- **PHP token_get_all()**: Too low-level, complex state management
- **Microsoft tolerant-php-parser**: Less adoption, different API
- **Custom parser**: Massive undertaking, reinventing the wheel
- **Regex-based**: Cannot handle PHP complexity accurately

**Tradeoffs**:
- ✅ Accuracy and completeness
- ✅ Community support
- ✅ Future PHP version support
- ❌ Additional dependency (mitigated by PHAR distribution)
- ❌ Slightly larger size (acceptable for accuracy)

### 2. Algorithm: Two-Phase Search

**Decision**: Implement two-phase search (fast filter + semantic validation).

**Rationale**:
- Proven pattern in all IDEs researched
- Balances performance with accuracy
- Allows early termination for obvious non-matches
- Scales well with codebase size

**Implementation**:
```
Phase 1: Text/Index Filtering (Fast)
- Simple string matching or trigram lookup
- Returns superset of possible matches
- O(log n) with index, O(n) without

Phase 2: AST-Based Validation (Accurate)
- Parse only candidate files
- Resolve symbols properly
- Calculate confidence scores
- O(m) where m = candidates
```

**Alternatives Considered**:
- **Full AST for everything**: Too slow for large codebases
- **Regex only**: Misses too many cases, false positives
- **Single-pass AST**: No early termination opportunity

### 3. Caching Strategy: File-Based JSON

**Decision**: Use file-based caching with JSON serialization.

**Rationale**:
- Simple and portable
- No external dependencies (Redis, Memcached)
- Works in any environment
- Easy to debug and inspect
- Sufficient for our performance needs

**Cache Structure**:
```
cache/
├── index.json          # Master symbol index
├── files/
│   └── [hash].json    # Per-file parse cache
└── metadata.json      # Version, settings, etc.
```

**Alternatives Considered**:
- **SQLite**: Overkill for our needs, another dependency
- **In-memory only**: Lost between runs
- **Binary format**: Harder to debug
- **No cache**: Unacceptable performance

**Cache Invalidation**:
- SHA-256 hash of file contents
- Compare modification time first (fast path)
- Invalidate dependencies when symbols change

### 4. Distribution: PHAR Archive

**Decision**: Distribute as a single PHAR file.

**Rationale**:
- Single file deployment
- No composer install needed by users
- Includes all dependencies
- Works directly with Docker
- Can be versioned and signed
- Standard practice (Composer, PHPUnit)

**Build Process**:
```bash
# Remove dev dependencies
composer install --no-dev --optimize-autoloader

# Create PHAR
php -d phar.readonly=0 build/create-phar.php

# Result: code-intel.phar (< 5MB)
```

**Alternatives Considered**:
- **Composer package**: Requires composer, dependency conflicts
- **Docker image only**: Limits usage scenarios
- **Multiple files**: Complex deployment
- **Binary extension**: Platform-specific, complex

### 5. PHP Version: 8.0+

**Decision**: Target PHP 8.0 as minimum version.

**Rationale**:
- Constructor property promotion (cleaner code)
- Union types (better type safety)
- Named arguments (better API)
- Match expressions (cleaner logic)
- Nullsafe operator (simpler code)
- PHP 7.4 EOL November 2022

**Compatibility**:
- Can analyze PHP 5.6+ code (via php-parser)
- Requires PHP 8.0+ to run
- PHAR works with any PHP 8.x Docker image

**Alternatives Considered**:
- **PHP 7.4**: Missing modern features, EOL
- **PHP 8.3 only**: Too restrictive for users
- **Multiple versions**: Maintenance nightmare

### 6. Output Format: Structured JSON

**Decision**: JSON output optimized for Claude Code consumption.

**Format**:
```json
{
  "symbol": "Full\\Qualified\\Name",
  "type": "class|method|property|function|constant",
  "usages": [
    {
      "file": "path/to/file.php",
      "line": 42,
      "confidence": "CERTAIN|PROBABLE|POSSIBLE|DYNAMIC",
      "code": "actual line of code",
      "context": {
        "start": 40,
        "end": 44,
        "lines": ["...", "...", "actual line", "...", "..."]
      }
    }
  ],
  "impact": {
    "direct_usages": 10,
    "inherited_usages": 5,
    "dynamic_usages": 2
  },
  "suggestions": [
    "Update interface Foo if changing method signature",
    "Check dynamic calls in EventDispatcher"
  ]
}
```

**Rationale**:
- Structured data perfect for AI parsing
- Include context for understanding
- Confidence levels guide decision-making
- Suggestions help with refactoring

**Alternatives Considered**:
- **Plain text**: Hard for Claude to parse reliably
- **XML**: Verbose, less common in PHP world
- **Custom format**: No benefit over JSON

### 7. Performance Targets

**Decision**: Specific performance targets based on research.

**Targets**:
- Initial index: < 10 seconds for 1000 files
- Find usage (cached): < 100ms
- Memory usage: < 100MB for 10,000 files
- PHAR size: < 5MB

**Rationale**:
- Based on IDE performance expectations
- Fast enough for interactive use
- Memory fits in typical Docker containers
- PHAR size reasonable for download

**Optimization Strategies**:
1. Lazy loading of data
2. Streaming results for large datasets
3. Parallel processing for initial index
4. Incremental cache updates

### 8. Architecture: Modular Components

**Decision**: Separate concerns into distinct modules.

**Structure**:
```
Parser/   - AST parsing and visitors
Index/    - Symbol storage and retrieval
Finder/   - Usage detection logic
Cache/    - Persistence layer
Output/   - Formatters
Commands/ - CLI interface
```

**Rationale**:
- Easy to test in isolation
- Clear responsibilities
- Extensible for future features
- Follows SOLID principles

### 9. What We're NOT Implementing

**Decided NOT to include**:

1. **Automated Refactoring**
   - Complexity beyond scope
   - Let Claude handle code changes
   - Focus on finding, not changing

2. **Cross-Repository Search**
   - Single project focus
   - Avoid complexity of dependencies
   - Can be added in v2

3. **Real-time Watching**
   - Batch operation model
   - Simplifies implementation
   - Cache handles incremental updates

4. **GUI/Web Interface**
   - CLI only for simplicity
   - Integrates better with Claude Code
   - Reduces complexity

5. **Language Server Protocol**
   - Overkill for our needs
   - Adds significant complexity
   - CLI is sufficient

6. **Custom Query Language**
   - Simple symbol names sufficient
   - Avoids parser complexity
   - Can extend later if needed

### 10. Testing Strategy

**Decision**: Comprehensive TDD with real fixtures.

**Approach**:
- Write tests first (TDD)
- Real PHP code fixtures, not mocks
- Cover every PHP construct
- Integration tests with real projects
- Performance benchmarks

**Rationale**:
- Confidence in correctness
- Catch edge cases early
- Document expected behavior
- Enable refactoring

### 11. Error Handling

**Decision**: Graceful degradation with clear reporting.

**Strategy**:
- Never crash on parse errors
- Report files that couldn't be analyzed
- Continue with partial results
- Clear error messages in JSON output

**Example**:
```json
{
  "errors": [
    {
      "file": "broken.php",
      "error": "Parse error on line 10",
      "severity": "warning"
    }
  ],
  "usages": [...] // Still return what we found
}
```

### 12. Future Considerations

**Designed for Extension**:

1. **Plugin System**: Architecture allows for custom visitors
2. **Additional Languages**: Parser abstraction enables expansion
3. **IDE Integration**: JSON output compatible with LSP
4. **Cloud Version**: Stateless design enables SaaS

**Version 2.0 Ideas**:
- Real-time file watching
- Cross-repository search
- Automated refactoring
- AI-powered suggestions
- Performance profiling

## Decision Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2024-01-13 | Use nikic/php-parser | Industry standard, proven |
| 2024-01-13 | Two-phase algorithm | Balance speed/accuracy |
| 2024-01-13 | File-based cache | Simple, portable |
| 2024-01-13 | PHAR distribution | Single file deployment |
| 2024-01-13 | JSON output | Claude-optimized |

---

*These decisions are based on extensive research of existing tools and optimization for Claude Code's specific needs. They prioritize accuracy, performance, and simplicity.*