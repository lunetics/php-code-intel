# Algorithms - PHP Code Intelligence Tool

## Overview

This document details the core algorithms used in the PHP Code Intelligence Tool, including pseudocode and implementation notes.

## 1. Two-Phase Search Algorithm

The core algorithm for finding symbol usages combines fast filtering with accurate validation.

### Phase 1: Fast Filtering

```
Algorithm: FastFilter
Input: symbolName (string), projectFiles (array)
Output: candidateFiles (array)

1. normalizedSymbol = extractSymbolParts(symbolName)
   // e.g., "UserService::update" → {class: "UserService", method: "update"}

2. candidateFiles = []

3. IF indexExists() THEN
     candidateFiles = queryIndex(normalizedSymbol)
   ELSE
     // Fallback to text search
     FOR EACH file IN projectFiles:
       IF fileContainsText(file, normalizedSymbol.text) THEN
         candidateFiles.append(file)
       END IF
     END FOR
   END IF

4. RETURN candidateFiles
```

### Phase 2: Semantic Validation

```
Algorithm: SemanticValidation
Input: candidateFiles (array), targetSymbol (string)
Output: usages (array)

1. usages = []
2. targetParts = parseTargetSymbol(targetSymbol)

3. FOR EACH file IN candidateFiles:
     ast = parseFile(file)
     fileUsages = findUsagesInAST(ast, targetParts, file)
     usages.append(fileUsages)
   END FOR

4. RETURN sortByConfidence(usages)
```

### Complete Two-Phase Process

```
Algorithm: FindSymbolUsages
Input: symbolName (string)
Output: usages (array)

1. // Phase 1: Fast filtering
   candidates = FastFilter(symbolName, getAllProjectFiles())
   
2. // Early exit optimization
   IF candidates.isEmpty() THEN
     RETURN []
   END IF

3. // Phase 2: Semantic validation
   usages = SemanticValidation(candidates, symbolName)
   
4. // Post-processing
   usages = addContextToUsages(usages)
   usages = calculateConfidenceScores(usages)
   
5. RETURN usages
```

## 2. Symbol Resolution Algorithm

Resolves symbol names to fully qualified names considering namespace context.

```
Algorithm: ResolveSymbol
Input: symbolName (string), currentContext (Context)
Output: fullyQualifiedName (string)

1. // Check if already fully qualified
   IF symbolName.startsWith('\') THEN
     RETURN symbolName
   END IF

2. // Check use statements
   FOR EACH import IN currentContext.imports:
     IF import.alias == symbolName OR import.name.endsWith(symbolName) THEN
       RETURN import.fullName
     END IF
   END FOR

3. // Check current namespace
   IF currentContext.namespace != null THEN
     fqn = currentContext.namespace + '\' + symbolName
     IF symbolExists(fqn) THEN
       RETURN fqn
     END IF
   END IF

4. // Check global namespace
   IF symbolExists('\' + symbolName) THEN
     RETURN '\' + symbolName
   END IF

5. // Special handling for built-in classes
   IF isBuiltInClass(symbolName) THEN
     RETURN '\' + symbolName
   END IF

6. RETURN null // Unable to resolve
```

### Context Building

```
Algorithm: BuildContext
Input: ast (AST), position (int)
Output: context (Context)

1. context = new Context()

2. // Find current namespace
   namespaceNode = findEnclosingNode(ast, position, 'Namespace')
   IF namespaceNode != null THEN
     context.namespace = namespaceNode.name
   END IF

3. // Collect use statements
   useStatements = findNodesBeforePosition(ast, position, 'Use')
   FOR EACH use IN useStatements:
     context.imports.add({
       fullName: use.name,
       alias: use.alias ?? extractLastPart(use.name)
     })
   END FOR

4. // Find current class
   classNode = findEnclosingNode(ast, position, 'Class')
   IF classNode != null THEN
     context.currentClass = classNode.name
     context.parentClass = classNode.extends
     context.interfaces = classNode.implements
   END IF

5. RETURN context
```

## 3. Confidence Scoring Algorithm

Determines how certain we are about a symbol usage.

```
Algorithm: CalculateConfidence
Input: usage (Usage), symbolInfo (SymbolInfo)
Output: confidence (string)

1. // Direct, unambiguous usage
   IF usage.type IN ['new', 'static_call', 'instanceof', 'type_declaration'] THEN
     RETURN 'CERTAIN'
   END IF

2. // Check for type information
   IF usage.type == 'method_call' THEN
     receiverType = inferType(usage.receiver)
     IF receiverType == symbolInfo.class THEN
       RETURN 'CERTAIN'
     ELSE IF receiverType != null AND isSubclassOf(receiverType, symbolInfo.class) THEN
       RETURN 'PROBABLE'
     ELSE IF hasTypeHint(usage.receiver) THEN
       RETURN 'PROBABLE'
     END IF
   END IF

3. // Dynamic constructs
   IF usage.type IN ['variable_call', 'variable_new'] THEN
     IF canTraceVariable(usage.variable) THEN
       RETURN 'POSSIBLE'
     ELSE
       RETURN 'DYNAMIC'
     END IF
   END IF

4. // Magic method usage
   IF symbolInfo.class.hasMagicMethod(usage.methodName) THEN
     RETURN 'DYNAMIC'
   END IF

5. // Default fallback
   RETURN 'POSSIBLE'
```

### Type Inference

```
Algorithm: InferType
Input: expression (Expression)
Output: type (string|null)

1. SWITCH expression.type:
     CASE 'variable':
       RETURN lookupVariableType(expression.name)
     
     CASE 'property_fetch':
       objectType = inferType(expression.object)
       RETURN lookupPropertyType(objectType, expression.property)
     
     CASE 'method_call':
       objectType = inferType(expression.object)
       RETURN lookupMethodReturnType(objectType, expression.method)
     
     CASE 'new':
       RETURN resolveClassName(expression.class)
     
     CASE 'this':
       RETURN getCurrentClassName()
     
     DEFAULT:
       RETURN null
   END SWITCH
```

## 4. Index Building Algorithm

Creates searchable index of all symbols in the codebase.

```
Algorithm: BuildSymbolIndex
Input: projectFiles (array)
Output: index (SymbolIndex)

1. index = new SymbolIndex()

2. // Phase 1: Extract symbols
   FOR EACH file IN projectFiles:
     IF shouldIndexFile(file) THEN
       symbols = extractSymbolsFromFile(file)
       FOR EACH symbol IN symbols:
         index.addSymbol(symbol)
       END FOR
     END IF
   END FOR

3. // Phase 2: Build relationships
   FOR EACH symbol IN index.getAllSymbols():
     IF symbol.type == 'class' THEN
       resolveInheritance(symbol, index)
       resolveTraits(symbol, index)
     END IF
   END FOR

4. // Phase 3: Build search structures
   index.buildTrigramIndex()
   index.buildNamespaceTree()
   index.calculateSymbolRanks()

5. RETURN index
```

### Incremental Index Update

```
Algorithm: UpdateIndex
Input: changedFiles (array), existingIndex (SymbolIndex)
Output: updatedIndex (SymbolIndex)

1. // Remove old symbols
   FOR EACH file IN changedFiles:
     existingIndex.removeSymbolsFromFile(file)
   END FOR

2. // Add new symbols
   FOR EACH file IN changedFiles:
     IF fileExists(file) THEN
       symbols = extractSymbolsFromFile(file)
       FOR EACH symbol IN symbols:
         existingIndex.addSymbol(symbol)
       END FOR
     END IF
   END FOR

3. // Update relationships for affected symbols
   affectedClasses = findAffectedClasses(changedFiles, existingIndex)
   FOR EACH class IN affectedClasses:
     resolveInheritance(class, existingIndex)
   END FOR

4. // Update search structures
   existingIndex.updateTrigramIndex(changedFiles)

5. RETURN existingIndex
```

## 5. Cache Management Algorithm

Efficiently manages file-based cache for performance.

```
Algorithm: CacheManagement
Input: request (CacheRequest)
Output: result (CacheResult)

1. cacheKey = generateCacheKey(request)
   
2. // Check if cache exists and is valid
   IF cacheExists(cacheKey) THEN
     cacheData = readCache(cacheKey)
     IF isCacheValid(cacheData, request) THEN
       RETURN cacheData.result
     END IF
   END IF

3. // Compute result
   result = computeResult(request)

4. // Store in cache
   cacheData = {
     result: result,
     timestamp: currentTime(),
     fileHashes: getFileHashes(request.files),
     version: CACHE_VERSION
   }
   writeCache(cacheKey, cacheData)

5. RETURN result
```

### Cache Invalidation

```
Algorithm: IsCacheValid
Input: cacheData (CacheData), request (Request)
Output: isValid (boolean)

1. // Check cache version
   IF cacheData.version != CACHE_VERSION THEN
     RETURN false
   END IF

2. // Check file modifications
   FOR EACH file IN request.files:
     currentHash = calculateFileHash(file)
     IF currentHash != cacheData.fileHashes[file] THEN
       RETURN false
     END IF
   END FOR

3. // Check timestamp (optional max age)
   IF currentTime() - cacheData.timestamp > MAX_CACHE_AGE THEN
     RETURN false
   END IF

4. RETURN true
```

## 6. Performance Optimization Algorithms

### Parallel Processing

```
Algorithm: ParallelIndexing
Input: files (array), workerCount (int)
Output: index (SymbolIndex)

1. // Split files into chunks
   chunks = splitArray(files, workerCount)
   
2. // Process chunks in parallel
   results = []
   FOR EACH chunk IN chunks PARALLEL:
     workerResult = processChunk(chunk)
     results.append(workerResult)
   END FOR

3. // Merge results
   finalIndex = new SymbolIndex()
   FOR EACH result IN results:
     finalIndex.merge(result)
   END FOR

4. RETURN finalIndex
```

### Memory-Efficient Streaming

```
Algorithm: StreamResults
Input: query (Query), maxMemory (int)
Output: resultGenerator (Generator)

1. FUNCTION* generateResults():
     buffer = []
     bufferSize = 0
     
     FOR EACH file IN findCandidateFiles(query):
       results = processFile(file, query)
       
       FOR EACH result IN results:
         buffer.append(result)
         bufferSize += estimateSize(result)
         
         IF bufferSize >= maxMemory THEN
           YIELD buffer
           buffer = []
           bufferSize = 0
         END IF
       END FOR
     END FOR
     
     IF buffer.notEmpty() THEN
       YIELD buffer
     END IF
   END FUNCTION

2. RETURN generateResults()
```

## 7. Text Search Optimization

### Trigram Index

```
Algorithm: BuildTrigramIndex
Input: symbols (array)
Output: trigramIndex (Map)

1. trigramIndex = new Map()

2. FOR EACH symbol IN symbols:
     trigrams = extractTrigrams(symbol.name)
     FOR EACH trigram IN trigrams:
       IF NOT trigramIndex.has(trigram) THEN
         trigramIndex[trigram] = []
       END IF
       trigramIndex[trigram].append(symbol.id)
     END FOR
   END FOR

3. RETURN trigramIndex
```

### Trigram Search

```
Algorithm: TrigramSearch
Input: query (string), trigramIndex (Map)
Output: candidates (array)

1. queryTrigrams = extractTrigrams(query)
   
2. // Find symbols containing all trigrams
   candidateSets = []
   FOR EACH trigram IN queryTrigrams:
     IF trigramIndex.has(trigram) THEN
       candidateSets.append(trigramIndex[trigram])
     ELSE
       RETURN [] // No matches possible
     END IF
   END FOR

3. // Intersection of all sets
   candidates = intersectSets(candidateSets)

4. // Rank by similarity
   ranked = []
   FOR EACH candidate IN candidates:
     score = calculateSimilarity(query, getSymbolName(candidate))
     ranked.append({id: candidate, score: score})
   END FOR

5. RETURN sortByScore(ranked)
```

## Implementation Notes

### Performance Considerations

1. **Lazy Loading**: Load ASTs only when needed
2. **Caching**: Cache parsed ASTs and symbol tables
3. **Early Exit**: Stop searching when enough results found
4. **Batch Operations**: Process multiple files together
5. **Memory Limits**: Stream large results

### Error Handling

1. **Parse Errors**: Skip file, log error, continue
2. **Memory Limits**: Fall back to streaming
3. **Timeout**: Return partial results
4. **Corrupted Cache**: Rebuild affected parts

### Optimization Opportunities

1. **Bloom Filters**: Quick negative lookups
2. **Compression**: Compress cache files
3. **Indexing**: Background index updates
4. **Precomputation**: Common query results

---

*These algorithms form the core of the PHP Code Intelligence Tool, balancing performance with accuracy for effective symbol usage finding.*