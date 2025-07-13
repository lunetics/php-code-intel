# Research Findings - PHP Code Analysis Tools

This document captures comprehensive research into how existing PHP tools handle symbol finding and code analysis.

## Table of Contents

1. [PHPStan Analysis](#phpstan-analysis)
2. [Rector Findings](#rector-findings)
3. [Psalm Architecture](#psalm-architecture)
4. [IDE Implementations](#ide-implementations)
5. [Key Takeaways](#key-takeaways)
6. [Technical Decisions](#technical-decisions)

## PHPStan Analysis

### Core Architecture

PHPStan uses nikic/php-parser for AST generation and implements a sophisticated analysis engine:

#### Key Components

1. **NodeScopeResolver**
   - Central analysis component
   - Processes AST nodes recursively
   - Maintains scope information throughout traversal
   - Located at `PHPStan\Analyser\NodeScopeResolver`

2. **Scope Object**
   - Tracks current analysis context
   - Provides type information: `getType(Expr $node)`
   - Resolves names: `resolveName(Name $name)`
   - Context awareness: `isInClass()`, `getClassReflection()`

3. **Dependency Tracking**
   - Maintains file dependency graph
   - Enables incremental analysis
   - When file changes, reanalyzes dependent files

### Symbol Discovery Process

```
1. Scan project paths + composer autoloader
2. Build initial symbol table
3. Parse files with php-parser
4. Traverse AST with NodeScopeResolver
5. Track dependencies between files
6. Cache results for performance
```

### Handling Dynamic Features

- **Magic Methods**: Via `@method` PHPDoc tags
- **Dynamic Properties**: Via `@property` tags
- **Mixins**: Via `@mixin` tag
- **Custom Extensions**: `DynamicMethodReturnTypeExtension`

### Caching Strategy

- **Result Cache**: Stores analysis results + dependency tree
- **Cache Location**: `%tmpDir%/resultCache.php`
- **Invalidation**: File changes, config changes, dependency updates
- **Performance**: Initial 5-10min → Cached 10-30s

### Key Insights

1. Uses two-phase approach (scan then analyze)
2. Heavy reliance on PHPDoc for dynamic features
3. Sophisticated caching with dependency tracking
4. Extension system for framework-specific logic

## Rector Findings

### Architecture Overview

Rector is built for automated refactoring using nikic/php-parser:

#### Core Concepts

1. **AbstractRector Base Class**
   ```php
   - getNodeTypes(): Define which AST nodes to process
   - refactor(Node $node): Transform the node
   - Uses type inference from PHPStan
   ```

2. **Node Traversal**
   - Visits each file's AST
   - Applies matching rules
   - Multiple passes for complex refactoring

3. **Symbol Detection Limitations**
   - Primarily single-file focused
   - Limited cross-file usage detection
   - Relies on PHPStan for type information

### Refactoring Approach

1. **Find Phase**: Locate nodes matching rule criteria
2. **Validate Phase**: Check if transformation is safe
3. **Transform Phase**: Modify AST nodes
4. **Verify Phase**: Ensure code still valid

### Handling Edge Cases

- **Dynamic Calls**: Often skipped or require manual review
- **Magic Methods**: Limited support
- **Skip Configuration**: Allow excluding problematic code

### Rule System

```php
interface RectorInterface {
    public function getNodeTypes(): array;
    public function refactor(Node $node): ?Node;
}
```

Example: `RenameMethodRector`
- Finds method calls matching old name
- Validates object type matches
- Renames to new method name

### Key Insights

1. Designed for local transformations
2. Weak at finding all usages across codebase
3. Leverages PHPStan's type system
4. Rule-based architecture is extensible

## Psalm Architecture

### Overview

Psalm implements comprehensive static analysis with focus on type safety:

### Symbol Reference Tracking

1. **FileReferenceProvider**
   - `addMethodReferenceToClass()`
   - `addPropertyReferenceToClass()`
   - Maintains bidirectional reference maps

2. **Storage System**
   - `ClassLikeStorage`: Class metadata
   - `FileStorage`: File-level information
   - Serialized for caching

3. **Codebase Class**
   - Central repository of all code information
   - Methods like `getMethodStorage()`, `getClassConstantStorage()`

### Two-Phase Scanning

**Phase 1: Shallow Scan**
- Extract signatures
- Build inheritance hierarchy
- Identify dependencies

**Phase 2: Deep Scan**
- Analyze method bodies
- Track all symbol usages
- Build complete dependency graph

### Advanced Features

1. **Trait Handling**
   - `@psalm-require-extends` annotation
   - Proper method resolution in trait context
   - Sealed properties/methods

2. **Performance Optimizations**
   - Selective deep scanning
   - Multi-threaded analysis
   - Aggressive caching with igbinary
   - File-based storage (can be 13GB+)

3. **Reference Finding**
   - CLI: `--find-references-to=Class::method`
   - Searches entire indexed codebase
   - Returns file:line references

### Key Insights

1. Comprehensive two-phase scanning
2. Extensive caching (storage + results)
3. Built for large-scale analysis
4. Strong trait and inheritance support

## IDE Implementations

### PhpStorm/IntelliJ Platform

**Architecture:**
1. **PSI (Program Structure Interface)**
   - Full AST representation
   - Lazy loading for performance

2. **Stub Indexes**
   - Compact binary format
   - Quick declaration lookups
   - Avoids full parsing

3. **Search Algorithm**
   ```
   1. Query word index for text matches
   2. Load stub tree for candidates
   3. Parse full PSI only if needed
   4. Resolve and verify references
   ```

### Language Server Protocol (LSP)

**Intelephense:**
- Maintains workspace-wide symbol index
- Near-instant reference finding
- Persistent index between sessions

**Phpactor Evolution:**
- Old: Regex-based, O(n*m) complexity
- New: Index-based, O(log n) lookups
- Learned from performance issues

### Microsoft's Tolerant Parser

**Design Principles:**
- Parse invalid/incomplete code
- Preserve all source information
- Incremental parsing support
- Full fidelity AST

### Common Patterns

1. **Two-Phase Funnel**
   - Phase 1: Coarse filtering (fast)
   - Phase 2: Semantic validation (accurate)

2. **Index Structures**
   - Trigram/word indexes for text
   - Symbol tables for declarations
   - Inverted indexes for usage lookup

3. **Caching Strategies**
   - AST caching with timestamps
   - Incremental updates
   - Memory-mapped files

## Key Takeaways

### Universal Patterns

1. **nikic/php-parser is Standard**
   - Used by PHPStan, Rector, Psalm
   - Maintained by PHP core developer
   - Handles all PHP syntax correctly

2. **Two-Phase Processing**
   - Fast filtering first
   - Detailed analysis second
   - Balance performance and accuracy

3. **Aggressive Caching Essential**
   - All tools cache extensively
   - File hashes for invalidation
   - Incremental updates critical

4. **PHPDoc for Dynamic Features**
   - Annotations bridge static/dynamic gap
   - `@method`, `@property`, `@mixin` standard
   - Type information crucial

5. **Index-Based Search**
   - Text/trigram indexes for speed
   - Symbol tables for relationships
   - Avoid full parsing when possible

### Performance Insights

| Tool | Initial Scan | Cached Run | Cache Size |
|------|--------------|------------|------------|
| PHPStan | 5-10 min | 10-30 sec | ~100MB |
| Psalm | 20+ min | <1 min | 1-13GB |
| PhpStorm | 2-5 min | Instant | ~500MB |

### Architectural Decisions

Based on research, our tool should:

1. **Use nikic/php-parser** - Industry standard, proven
2. **Implement two-phase search** - Performance critical
3. **Build comprehensive index** - Include all symbols
4. **Cache aggressively** - File-based for simplicity
5. **Support annotations** - Handle dynamic PHP
6. **Optimize for Claude** - Structured JSON output

## Technical Decisions

### Parser Choice: nikic/php-parser

**Reasons:**
- Universal adoption (PHPStan, Rector, Psalm)
- Comprehensive PHP support (5.2 to 8.3)
- Stable API
- Excellent documentation
- Visitor pattern for extensibility

### Algorithm: Two-Phase Search

**Phase 1: Filtering**
- Text-based or index lookup
- Returns candidate locations
- O(log n) with index

**Phase 2: Validation**
- Parse candidates with php-parser
- Resolve symbols accurately
- Score confidence levels

### Distribution: PHAR

**Benefits:**
- Single file distribution
- Includes all dependencies
- No composer for users
- Works with Docker directly
- Can be versioned/signed

### Performance Strategy

1. **Initial Index**: Accept slower first run
2. **Incremental Updates**: Only reparse changed files
3. **Memory Efficiency**: Stream large results
4. **Parallel Processing**: For initial indexing

This research forms the foundation for building a best-in-class PHP symbol finder optimized for Claude Code's needs.