# Advanced Usage Guide

## Overview

This guide covers advanced usage scenarios, complex integrations, and expert-level features of the PHP Code Intelligence Tool, including Docker runtime container usage for environments without local PHP installation.

## Docker Runtime Container Usage

### Quick Start with Docker Runtime Container

For environments where PHP is not installed locally, use the Docker runtime container:

```bash
# One-time setup
make build-runtime

# Run analysis
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  find-usages "App\\User" --path=src/ --format=json

# Or use the setup script for convenience
./scripts/runtime-setup.sh
php-code-intel find-usages "App\\User" --path=src/
```

### Advanced Docker Runtime Scenarios

#### Batch Processing with Docker
```bash
# Process multiple symbols efficiently
symbols=("App\\Models\\User" "App\\Services\\UserService" "App\\Controllers\\UserController")

for symbol in "${symbols[@]}"; do
    echo "Analyzing: $symbol"
    docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
        find-usages "$symbol" --path=src/ --format=json > "analysis-$(echo $symbol | tr '\\' '-').json"
done
```

#### Development vs Production Containers
```bash
# Build development container with debugging tools
make build-runtime-dev

# Use development container for debugging
docker run --rm -v $(pwd):/workspace php-code-intel:runtime-dev \
    find-usages "App\\User" --path=src/ --verbose

# Production container for CI/CD
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
    find-usages "App\\User" --path=src/ --format=json --confidence=CERTAIN
```

#### Memory-Optimized Docker Processing
```bash
# For large projects, limit memory and use streaming
docker run --rm \
    -v $(pwd):/workspace \
    --memory=512m \
    --memory-swap=1g \
    php-code-intel:runtime \
    find-usages "LargeClass" --path=src/ --format=json
```

#### Docker Compose for Team Development
```yaml
# docker-compose.override.yml for team development
services:
  analysis:
    extends:
      file: docker-compose.runtime.yml
      service: php-code-intel
    volumes:
      - .:/workspace
      - analysis-cache:/tmp/analysis
    environment:
      - ANALYSIS_CACHE_DIR=/tmp/analysis
```

#### Multi-Project Analysis with Docker
```bash
#!/bin/bash
# analyze-multiple-projects.sh

projects=(
    "/path/to/project1"
    "/path/to/project2"
    "/path/to/project3"
)

# Build runtime container once
make build-runtime

for project in "${projects[@]}"; do
    echo "Analyzing $(basename $project)..."
    
    docker run --rm \
        -v "$project:/workspace" \
        -v "$(pwd)/reports:/reports" \
        php-code-intel:runtime \
        find-usages "CommonInterface" --path=src/ --format=json \
        > "reports/$(basename $project)-analysis.json"
done
```

## Complex Symbol Analysis

### Finding Inherited Method Usages

```php
// Find all usages of methods that might be inherited
$usages = [];

// Direct method usage
$directUsages = $finder->find('BaseClass::processData');

// Child class usages (manually check inheritance)
$childClasses = ['ChildClass', 'AnotherChild', 'ExtendedClass'];
foreach ($childClasses as $child) {
    $childUsages = $finder->find("{$child}::processData");
    $usages = array_merge($usages, $childUsages);
}

// Interface method implementations
$interfaceUsages = $finder->find('ProcessorInterface::processData');
$usages = array_merge($usages, $interfaceUsages);

// Filter by confidence level
$certainUsages = array_filter($usages, fn($u) => $u['confidence'] === 'CERTAIN');
```

### Trait Method Detection

```php
// Find trait usage across multiple classes
$traitMethods = [
    'TimestampableTrait::updateTimestamps',
    'TimestampableTrait::getCreatedAt',
    'TimestampableTrait::getUpdatedAt'
];

$traitUsageReport = [];
foreach ($traitMethods as $method) {
    $usages = $finder->find($method);
    
    $traitUsageReport[$method] = [
        'total_usages' => count($usages),
        'files' => array_unique(array_column($usages, 'file')),
        'high_confidence' => array_filter($usages, fn($u) => 
            in_array($u['confidence'], ['CERTAIN', 'PROBABLE'])
        )
    ];
}

// Generate report
foreach ($traitUsageReport as $method => $data) {
    echo "Method: {$method}\n";
    echo "  Total usages: {$data['total_usages']}\n";
    echo "  Files using: " . count($data['files']) . "\n";
    echo "  High confidence: " . count($data['high_confidence']) . "\n\n";
}
```

## Batch Processing and Analysis

### Large Codebase Processing

```php
use CodeIntel\Index\SymbolIndex;
use CodeIntel\Finder\UsageFinder;
use CodeIntel\Error\ErrorLogger;
use CodeIntel\Error\ErrorSeverity;

class LargeCodebaseAnalyzer
{
    private SymbolIndex $index;
    private UsageFinder $finder;
    private ErrorLogger $errorLogger;
    
    public function __construct()
    {
        $this->errorLogger = new ErrorLogger(
            maxErrors: 2000,
            minSeverity: ErrorSeverity::WARNING
        );
        
        $this->index = new SymbolIndex();
        $this->finder = new UsageFinder($this->index, $this->errorLogger);
    }
    
    public function analyzeLargeProject(string $projectPath): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Index files in batches
        $files = $this->getAllPhpFiles($projectPath);
        $batches = array_chunk($files, 100); // Process 100 files at a time
        
        foreach ($batches as $batchIndex => $batch) {
            echo "Processing batch " . ($batchIndex + 1) . "/" . count($batches) . "\n";
            
            foreach ($batch as $file) {
                try {
                    $this->index->addFile($file);
                } catch (\Throwable $e) {
                    $this->errorLogger->logIoError($file, $e->getMessage(), $e);
                }
            }
            
            // Memory management
            if (memory_get_usage(true) > 256 * 1024 * 1024) { // 256MB
                echo "High memory usage detected, forcing garbage collection\n";
                gc_collect_cycles();
            }
            
            // Check error threshold
            if ($this->errorLogger->hasExceededErrorThreshold(count($files), 0.1)) {
                throw new \RuntimeException("Too many errors during indexing");
            }
        }
        
        $indexTime = microtime(true) - $startTime;
        
        return [
            'files_indexed' => count($files),
            'index_time' => $indexTime,
            'memory_used' => memory_get_usage(true) - $startMemory,
            'errors' => $this->errorLogger->getErrorSummary()
        ];
    }
    
    public function findSymbolUsagesAcrossProject(array $symbols): array
    {
        $results = [];
        
        foreach ($symbols as $symbol) {
            $startTime = microtime(true);
            
            try {
                $usages = $this->finder->find($symbol);
                
                $results[$symbol] = [
                    'usages' => $usages,
                    'count' => count($usages),
                    'files' => array_unique(array_column($usages, 'file')),
                    'confidence_breakdown' => $this->getConfidenceBreakdown($usages),
                    'search_time' => microtime(true) - $startTime
                ];
                
            } catch (\Throwable $e) {
                $results[$symbol] = [
                    'error' => $e->getMessage(),
                    'usages' => [],
                    'count' => 0
                ];
            }
        }
        
        return $results;
    }
    
    private function getConfidenceBreakdown(array $usages): array
    {
        $breakdown = ['CERTAIN' => 0, 'PROBABLE' => 0, 'POSSIBLE' => 0, 'DYNAMIC' => 0];
        
        foreach ($usages as $usage) {
            $breakdown[$usage['confidence']]++;
        }
        
        return $breakdown;
    }
    
    private function getAllPhpFiles(string $path): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        return $phpFiles;
    }
}

// Usage
$analyzer = new LargeCodebaseAnalyzer();

// Index large project
$indexResults = $analyzer->analyzeLargeProject('/path/to/large/project');
echo "Indexed {$indexResults['files_indexed']} files in {$indexResults['index_time']}s\n";

// Find multiple symbols
$symbols = [
    'App\\Models\\User',
    'App\\Services\\UserService', 
    'App\\Controllers\\UserController',
    'User::getName',
    'UserService::findById'
];

$usageResults = $analyzer->findSymbolUsagesAcrossProject($symbols);

// Generate comprehensive report
foreach ($usageResults as $symbol => $data) {
    if (isset($data['error'])) {
        echo "ERROR for {$symbol}: {$data['error']}\n";
        continue;
    }
    
    echo "\nSymbol: {$symbol}\n";
    echo "  Total usages: {$data['count']}\n";
    echo "  Files affected: " . count($data['files']) . "\n";
    echo "  Search time: {$data['search_time']}s\n";
    echo "  Confidence breakdown:\n";
    
    foreach ($data['confidence_breakdown'] as $level => $count) {
        if ($count > 0) {
            echo "    {$level}: {$count}\n";
        }
    }
}
```

## Refactoring Automation

### Safe Class Renaming

```php
class SafeClassRenamer
{
    private UsageFinder $finder;
    private array $dryRunResults = [];
    
    public function __construct(UsageFinder $finder)
    {
        $this->finder = $finder;
    }
    
    public function planClassRename(string $oldClass, string $newClass): array
    {
        // Find all usages
        $usages = $this->finder->find($oldClass);
        
        // Categorize by usage type and confidence
        $plan = [
            'old_class' => $oldClass,
            'new_class' => $newClass,
            'total_usages' => count($usages),
            'safe_replacements' => [],
            'risky_replacements' => [],
            'manual_review_needed' => [],
            'affected_files' => []
        ];
        
        foreach ($usages as $usage) {
            $replacement = [
                'file' => $usage['file'],
                'line' => $usage['line'],
                'old_code' => $usage['code'],
                'new_code' => $this->generateReplacement($usage['code'], $oldClass, $newClass),
                'confidence' => $usage['confidence'],
                'type' => $usage['type'] ?? 'unknown'
            ];
            
            // Categorize based on confidence and type
            if ($usage['confidence'] === 'CERTAIN' && $this->isSafeReplacement($usage)) {
                $plan['safe_replacements'][] = $replacement;
            } elseif ($usage['confidence'] === 'DYNAMIC') {
                $plan['manual_review_needed'][] = $replacement;
            } else {
                $plan['risky_replacements'][] = $replacement;
            }
            
            $plan['affected_files'][] = $usage['file'];
        }
        
        $plan['affected_files'] = array_unique($plan['affected_files']);
        
        return $plan;
    }
    
    public function executeRename(array $plan, bool $dryRun = true): array
    {
        $results = [
            'files_modified' => 0,
            'replacements_made' => 0,
            'errors' => [],
            'backup_files' => []
        ];
        
        foreach ($plan['safe_replacements'] as $replacement) {
            try {
                if (!$dryRun) {
                    // Create backup
                    $backupFile = $replacement['file'] . '.backup.' . time();
                    copy($replacement['file'], $backupFile);
                    $results['backup_files'][] = $backupFile;
                    
                    // Perform replacement
                    $content = file_get_contents($replacement['file']);
                    $newContent = str_replace(
                        $replacement['old_code'],
                        $replacement['new_code'],
                        $content
                    );
                    
                    file_put_contents($replacement['file'], $newContent);
                    $results['files_modified']++;
                }
                
                $results['replacements_made']++;
                
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'file' => $replacement['file'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private function generateReplacement(string $code, string $oldClass, string $newClass): string
    {
        // Handle different usage patterns
        $patterns = [
            "/\\bnew\\s+{$oldClass}\\s*\\(/",
            "/{$oldClass}::/",
            "/instanceof\\s+{$oldClass}/",
            "/class\\s+\\w+\\s+extends\\s+{$oldClass}/",
            "/implements\\s+.*{$oldClass}/",
            "/\\\\{$oldClass}\\b/",
            "/use\\s+.*{$oldClass}/"
        ];
        
        $newCode = $code;
        foreach ($patterns as $pattern) {
            $newCode = preg_replace($pattern, str_replace($oldClass, $newClass, '$0'), $newCode);
        }
        
        return $newCode;
    }
    
    private function isSafeReplacement(array $usage): bool
    {
        // Check for safe replacement patterns
        $safePatterns = [
            'new ' . $usage['code'],
            '::class',
            'instanceof',
            'extends',
            'implements'
        ];
        
        foreach ($safePatterns as $pattern) {
            if (str_contains($usage['code'], $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}

// Usage example
$renamer = new SafeClassRenamer($finder);

// Plan the rename
$renamePlan = $renamer->planClassRename('OldUser', 'NewUser');

echo "Rename plan for OldUser -> NewUser:\n";
echo "Total usages: {$renamePlan['total_usages']}\n";
echo "Safe replacements: " . count($renamePlan['safe_replacements']) . "\n";
echo "Risky replacements: " . count($renamePlan['risky_replacements']) . "\n";
echo "Manual review needed: " . count($renamePlan['manual_review_needed']) . "\n";
echo "Affected files: " . count($renamePlan['affected_files']) . "\n";

// Dry run
$dryRunResults = $renamer->executeRename($renamePlan, true);
echo "\nDry run results:\n";
echo "Would modify {$dryRunResults['files_modified']} files\n";
echo "Would make {$dryRunResults['replacements_made']} replacements\n";

// Actual execution (after review)
if (readline("Proceed with actual rename? (y/N): ") === 'y') {
    $actualResults = $renamer->executeRename($renamePlan, false);
    echo "Rename completed: {$actualResults['files_modified']} files modified\n";
}
```

## Performance Optimization

### Memory-Efficient Large File Processing

```php
class MemoryEfficientProcessor
{
    private int $memoryLimit;
    private UsageFinder $finder;
    private ErrorLogger $errorLogger;
    
    public function __construct(UsageFinder $finder, int $memoryLimitMB = 256)
    {
        $this->memoryLimit = $memoryLimitMB * 1024 * 1024;
        $this->finder = $finder;
        $this->errorLogger = $finder->getErrorLogger();
    }
    
    public function processLargeFileSet(array $files, array $symbols): \Generator
    {
        $batchSize = 50;
        $batches = array_chunk($files, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            $batchResults = [];
            
            foreach ($batch as $file) {
                // Monitor memory usage
                if (memory_get_usage(true) > $this->memoryLimit) {
                    // Yield current batch and clear memory
                    yield $batchResults;
                    $batchResults = [];
                    gc_collect_cycles();
                }
                
                try {
                    $this->finder->getIndex()->addFile($file);
                    
                    // Process symbols for this file
                    foreach ($symbols as $symbol) {
                        $usages = $this->finder->find($symbol);
                        $fileUsages = array_filter($usages, fn($u) => $u['file'] === $file);
                        
                        if (!empty($fileUsages)) {
                            $batchResults[$file][$symbol] = $fileUsages;
                        }
                    }
                    
                } catch (\Throwable $e) {
                    $this->errorLogger->logIoError($file, $e->getMessage(), $e);
                }
            }
            
            if (!empty($batchResults)) {
                yield $batchResults;
            }
        }
    }
    
    public function generateProgressReport(\Generator $processor): array
    {
        $report = [
            'files_processed' => 0,
            'symbols_found' => 0,
            'memory_peaks' => [],
            'processing_times' => []
        ];
        
        foreach ($processor as $batchIndex => $batchResults) {
            $startTime = microtime(true);
            $memoryUsage = memory_get_usage(true);
            
            $report['files_processed'] += count($batchResults);
            
            foreach ($batchResults as $file => $symbolUsages) {
                foreach ($symbolUsages as $symbol => $usages) {
                    $report['symbols_found'] += count($usages);
                }
            }
            
            $report['memory_peaks'][] = $memoryUsage;
            $report['processing_times'][] = microtime(true) - $startTime;
            
            echo "Batch {$batchIndex}: " . count($batchResults) . " files, " . 
                 number_format($memoryUsage / 1024 / 1024, 2) . "MB memory\n";
        }
        
        return $report;
    }
}

// Usage
$processor = new MemoryEfficientProcessor($finder, 256); // 256MB limit
$files = glob('/path/to/project/**/*.php', GLOB_BRACE);
$symbols = ['User', 'UserService', 'UserController'];

$generator = $processor->processLargeFileSet($files, $symbols);
$report = $processor->generateProgressReport($generator);

echo "\nProcessing complete:\n";
echo "Files processed: {$report['files_processed']}\n";
echo "Symbols found: {$report['symbols_found']}\n";
echo "Peak memory: " . number_format(max($report['memory_peaks']) / 1024 / 1024, 2) . "MB\n";
echo "Average batch time: " . number_format(array_sum($report['processing_times']) / count($report['processing_times']), 3) . "s\n";
```

## Custom Output Formatters

### Creating Custom Report Formats

```php
class CustomReportFormatter
{
    public function generateMarkdownReport(array $usageResults): string
    {
        $markdown = "# Symbol Usage Report\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($usageResults as $symbol => $data) {
            $markdown .= "## Symbol: `{$symbol}`\n\n";
            
            if (isset($data['error'])) {
                $markdown .= "❌ **Error:** {$data['error']}\n\n";
                continue;
            }
            
            $markdown .= "- **Total Usages:** {$data['count']}\n";
            $markdown .= "- **Files Affected:** " . count($data['files']) . "\n";
            $markdown .= "- **Search Time:** {$data['search_time']}s\n\n";
            
            // Confidence breakdown
            $markdown .= "### Confidence Breakdown\n\n";
            foreach ($data['confidence_breakdown'] as $level => $count) {
                if ($count > 0) {
                    $icon = $this->getConfidenceIcon($level);
                    $markdown .= "- {$icon} **{$level}:** {$count}\n";
                }
            }
            $markdown .= "\n";
            
            // Usage details
            if (!empty($data['usages'])) {
                $markdown .= "### Usage Details\n\n";
                
                foreach ($data['usages'] as $usage) {
                    $file = basename($usage['file']);
                    $confidence = $usage['confidence'];
                    $icon = $this->getConfidenceIcon($confidence);
                    
                    $markdown .= "#### {$file}:{$usage['line']}\n\n";
                    $markdown .= "```php\n{$usage['code']}\n```\n\n";
                    $markdown .= "**Confidence:** {$icon} {$confidence}\n\n";
                }
            }
            
            $markdown .= "---\n\n";
        }
        
        return $markdown;
    }
    
    public function generateHtmlReport(array $usageResults): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Symbol Usage Report</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .symbol { border: 1px solid #ddd; margin: 20px 0; padding: 20px; border-radius: 8px; }
        .confidence-certain { color: #28a745; }
        .confidence-probable { color: #17a2b8; }
        .confidence-possible { color: #ffc107; }
        .confidence-dynamic { color: #dc3545; }
        .usage { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 4px; }
        pre { background: #e9ecef; padding: 10px; border-radius: 4px; }
        .stats { display: flex; gap: 20px; margin: 10px 0; }
        .stat { background: #e3f2fd; padding: 10px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>';
        
        $html .= '<h1>Symbol Usage Report</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
        
        foreach ($usageResults as $symbol => $data) {
            $html .= "<div class='symbol'>";
            $html .= "<h2>Symbol: <code>{$symbol}</code></h2>";
            
            if (isset($data['error'])) {
                $html .= "<div class='alert alert-danger'>Error: {$data['error']}</div>";
                $html .= "</div>";
                continue;
            }
            
            // Statistics
            $html .= "<div class='stats'>";
            $html .= "<div class='stat'><strong>{$data['count']}</strong><br>Total Usages</div>";
            $html .= "<div class='stat'><strong>" . count($data['files']) . "</strong><br>Files</div>";
            $html .= "<div class='stat'><strong>{$data['search_time']}s</strong><br>Search Time</div>";
            $html .= "</div>";
            
            // Confidence breakdown
            $html .= "<h3>Confidence Breakdown</h3>";
            foreach ($data['confidence_breakdown'] as $level => $count) {
                if ($count > 0) {
                    $class = 'confidence-' . strtolower($level);
                    $html .= "<span class='{$class}'><strong>{$level}:</strong> {$count}</span> ";
                }
            }
            
            // Usage details
            if (!empty($data['usages'])) {
                $html .= "<h3>Usage Details</h3>";
                
                foreach ($data['usages'] as $usage) {
                    $file = basename($usage['file']);
                    $confidence = $usage['confidence'];
                    $class = 'confidence-' . strtolower($confidence);
                    
                    $html .= "<div class='usage'>";
                    $html .= "<strong>{$file}:{$usage['line']}</strong>";
                    $html .= " <span class='{$class}'>({$confidence})</span>";
                    $html .= "<pre>" . htmlspecialchars($usage['code']) . "</pre>";
                    $html .= "</div>";
                }
            }
            
            $html .= "</div>";
        }
        
        $html .= '</body></html>';
        return $html;
    }
    
    public function generateCsvReport(array $usageResults): string
    {
        $csv = "Symbol,File,Line,Code,Confidence,Type\n";
        
        foreach ($usageResults as $symbol => $data) {
            if (isset($data['error'])) {
                $csv .= '"' . $symbol . '","ERROR","","' . $data['error'] . '","",""\n';
                continue;
            }
            
            foreach ($data['usages'] as $usage) {
                $csv .= '"' . $symbol . '",';
                $csv .= '"' . $usage['file'] . '",';
                $csv .= $usage['line'] . ',';
                $csv .= '"' . str_replace('"', '""', $usage['code']) . '",';
                $csv .= '"' . $usage['confidence'] . '",';
                $csv .= '"' . ($usage['type'] ?? '') . '"';
                $csv .= "\n";
            }
        }
        
        return $csv;
    }
    
    private function getConfidenceIcon(string $confidence): string
    {
        return match($confidence) {
            'CERTAIN' => '✅',
            'PROBABLE' => '✅',
            'POSSIBLE' => '⚠️',
            'DYNAMIC' => '❓',
            default => '❔'
        };
    }
}

// Usage
$formatter = new CustomReportFormatter();

// Generate different report formats
$markdownReport = $formatter->generateMarkdownReport($usageResults);
file_put_contents('symbol-usage-report.md', $markdownReport);

$htmlReport = $formatter->generateHtmlReport($usageResults);
file_put_contents('symbol-usage-report.html', $htmlReport);

$csvReport = $formatter->generateCsvReport($usageResults);
file_put_contents('symbol-usage-report.csv', $csvReport);

echo "Reports generated:\n";
echo "- symbol-usage-report.md\n";
echo "- symbol-usage-report.html\n";
echo "- symbol-usage-report.csv\n";
```

## Integration Examples

### CI/CD Pipeline Integration

```bash
#!/bin/bash
# ci-symbol-analysis.sh

set -e

PROJECT_PATH="/path/to/project"
REPORT_DIR="reports"
SYMBOLS_FILE="symbols-to-check.txt"

mkdir -p $REPORT_DIR

# List of critical symbols to monitor
cat > $SYMBOLS_FILE << EOF
App\\Models\\User
App\\Services\\UserService
App\\Controllers\\UserController
User::getName
UserService::findById
EOF

echo "Starting symbol usage analysis..."

# Run analysis for each symbol
while IFS= read -r symbol; do
    echo "Analyzing symbol: $symbol"
    
    # Generate JSON report
    php bin/php-code-intel find-usages "$symbol" \
        --path="src/" \
        --format=json \
        --exclude=vendor \
        --exclude=tests > "$REPORT_DIR/usage-$symbol.json" || echo "Failed to analyze $symbol"
        
done < $SYMBOLS_FILE

# Generate summary report
php -r "
\$symbols = file('$SYMBOLS_FILE', FILE_IGNORE_NEW_LINES);
\$summary = ['total_symbols' => count(\$symbols), 'results' => []];

foreach (\$symbols as \$symbol) {
    \$file = '$REPORT_DIR/usage-' . \$symbol . '.json';
    if (file_exists(\$file)) {
        \$data = json_decode(file_get_contents(\$file), true);
        \$summary['results'][\$symbol] = [
            'usages' => count(\$data),
            'files' => count(array_unique(array_column(\$data, 'file')))
        ];
    }
}

file_put_contents('$REPORT_DIR/summary.json', json_encode(\$summary, JSON_PRETTY_PRINT));
echo 'Analysis complete. Summary saved to $REPORT_DIR/summary.json';
"

# Clean up
rm $SYMBOLS_FILE
```

### IDE Integration Example

```php
// ide-integration.php - Example IDE plugin backend

class IDEIntegration
{
    private UsageFinder $finder;
    
    public function __construct()
    {
        $this->finder = new UsageFinder(new SymbolIndex());
    }
    
    public function handleRequest(array $request): array
    {
        switch ($request['action']) {
            case 'find_usages':
                return $this->findUsages($request);
            case 'get_symbol_info':
                return $this->getSymbolInfo($request);
            case 'validate_rename':
                return $this->validateRename($request);
            default:
                return ['error' => 'Unknown action'];
        }
    }
    
    private function findUsages(array $request): array
    {
        $symbol = $request['symbol'] ?? '';
        $projectPath = $request['project_path'] ?? '';
        
        if (!$symbol || !$projectPath) {
            return ['error' => 'Missing required parameters'];
        }
        
        // Index project files
        $files = glob($projectPath . '/**/*.php', GLOB_BRACE);
        foreach ($files as $file) {
            $this->finder->getIndex()->addFile($file);
        }
        
        // Find usages
        $usages = $this->finder->find($symbol);
        
        // Format for IDE
        $formattedUsages = [];
        foreach ($usages as $usage) {
            $formattedUsages[] = [
                'file' => $usage['file'],
                'line' => $usage['line'],
                'column' => 0, // Calculate if needed
                'preview' => trim($usage['code']),
                'confidence' => $usage['confidence']
            ];
        }
        
        return [
            'symbol' => $symbol,
            'usages' => $formattedUsages,
            'total' => count($formattedUsages)
        ];
    }
    
    private function getSymbolInfo(array $request): array
    {
        $symbol = $request['symbol'] ?? '';
        $usages = $this->finder->find($symbol);
        
        $info = [
            'symbol' => $symbol,
            'total_usages' => count($usages),
            'files_count' => count(array_unique(array_column($usages, 'file'))),
            'confidence_breakdown' => [
                'CERTAIN' => 0,
                'PROBABLE' => 0,
                'POSSIBLE' => 0,
                'DYNAMIC' => 0
            ],
            'usage_types' => []
        ];
        
        foreach ($usages as $usage) {
            $info['confidence_breakdown'][$usage['confidence']]++;
            
            if (isset($usage['type'])) {
                $info['usage_types'][$usage['type']] = 
                    ($info['usage_types'][$usage['type']] ?? 0) + 1;
            }
        }
        
        return $info;
    }
    
    private function validateRename(array $request): array
    {
        $oldSymbol = $request['old_symbol'] ?? '';
        $newSymbol = $request['new_symbol'] ?? '';
        
        $usages = $this->finder->find($oldSymbol);
        
        $validation = [
            'safe_to_rename' => true,
            'warnings' => [],
            'blockers' => [],
            'affected_files' => count(array_unique(array_column($usages, 'file')))
        ];
        
        foreach ($usages as $usage) {
            if ($usage['confidence'] === 'DYNAMIC') {
                $validation['warnings'][] = [
                    'file' => $usage['file'],
                    'line' => $usage['line'],
                    'message' => 'Dynamic usage detected - manual review needed'
                ];
            }
            
            if (str_contains($usage['code'], '__call') || str_contains($usage['code'], 'call_user_func')) {
                $validation['blockers'][] = [
                    'file' => $usage['file'],
                    'line' => $usage['line'],
                    'message' => 'Reflection or magic method usage - automatic rename not safe'
                ];
                $validation['safe_to_rename'] = false;
            }
        }
        
        return $validation;
    }
}

// Example usage as JSON API
header('Content-Type: application/json');

$request = json_decode(file_get_contents('php://input'), true);
$integration = new IDEIntegration();
$response = $integration->handleRequest($request);

echo json_encode($response);
```

---

**This advanced usage guide demonstrates sophisticated applications of the PHP Code Intelligence Tool for complex analysis, refactoring automation, and integration scenarios.**