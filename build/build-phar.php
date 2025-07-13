<?php

declare(strict_types=1);

/*
 * PHAR Builder for PHP Code Intelligence Tool
 * 
 * Creates a self-contained executable PHAR archive
 */

// Check if we can create PHARs
if (!Phar::canWrite()) {
    fwrite(STDERR, "PHAR creation is disabled. Please set phar.readonly=0 in php.ini\n");
    exit(1);
}

// Configuration
$rootDir = dirname(__DIR__);
$buildDir = __DIR__;
$pharFile = $buildDir . '/php-code-intel.phar';
$stubFile = $buildDir . '/stub.php';

// Clean up any existing PHAR
if (file_exists($pharFile)) {
    unlink($pharFile);
}

echo "Building PHAR archive...\n";

try {
    // Create the PHAR
    $phar = new Phar($pharFile);
    $phar->startBuffering();
    
    // Add the main application directory
    $phar->buildFromDirectory($rootDir, '/^(?!.*(?:build|tests|coverage|\.git|\.docker|docker-compose)).*$/');
    
    // Remove development dependencies from vendor if they exist
    $vendorIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir . '/vendor'),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($vendorIterator as $file) {
        $relativePath = str_replace($rootDir . '/', '', $file->getPathname());
        
        // Skip development-only packages
        if (strpos($relativePath, 'vendor/phpunit/') !== false ||
            strpos($relativePath, 'vendor/sebastian/') !== false ||
            strpos($relativePath, 'vendor/myclabs/') !== false ||
            strpos($relativePath, 'vendor/phar-io/') !== false ||
            strpos($relativePath, 'vendor/theseer/') !== false) {
            continue;
        }
        
        if ($file->isFile()) {
            $phar->addFile($file->getPathname(), $relativePath);
        }
    }
    
    // Create and set the stub
    $stub = createStub();
    $phar->setStub($stub);
    
    // Stop buffering and finalize
    $phar->stopBuffering();
    
    // Make executable
    chmod($pharFile, 0755);
    
    echo "PHAR created successfully: " . $pharFile . "\n";
    echo "Size: " . formatBytes(filesize($pharFile)) . "\n";
    
    // Test the PHAR
    echo "\nTesting PHAR...\n";
    $output = shell_exec("php $pharFile --version 2>&1");
    if (strpos($output, 'PHP Code Intelligence Tool') !== false) {
        echo "✓ PHAR test successful\n";
    } else {
        echo "✗ PHAR test failed\n";
        echo "Output: $output\n";
        exit(1);
    }
    
} catch (Exception $e) {
    fwrite(STDERR, "Error building PHAR: " . $e->getMessage() . "\n");
    exit(1);
}

function createStub(): string
{
    return <<<'STUB'
#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * PHP Code Intelligence Tool - PHAR Stub
 * 
 * Self-contained executable for finding PHP symbol usages
 * Optimized for integration with Claude Code for accurate refactoring
 */

// Check if running as PHAR
if (!class_exists('Phar') || !Phar::running(false)) {
    // Running as regular PHP file, this is fine
}

Phar::mapPhar('php-code-intel.phar');

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    fwrite(STDERR, "PHP Code Intelligence Tool requires PHP 8.0 or higher. Current version: " . PHP_VERSION . "\n");
    exit(1);
}

// Check required extensions
$requiredExtensions = ['json', 'mbstring', 'tokenizer'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (!empty($missingExtensions)) {
    fwrite(STDERR, "Missing required PHP extensions: " . implode(', ', $missingExtensions) . "\n");
    exit(1);
}

// Set error handling
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Increase memory limit if needed
$memoryLimit = ini_get('memory_limit');
if ($memoryLimit !== '-1' && (int)$memoryLimit < 128) {
    ini_set('memory_limit', '128M');
}

// Load autoloader
require 'phar://php-code-intel.phar/vendor/autoload.php';

use CodeIntel\Console\Application;

try {
    $application = new Application();
    $exitCode = $application->run();
    exit($exitCode);
} catch (Throwable $e) {
    fwrite(STDERR, "Fatal error: " . $e->getMessage() . "\n");
    
    if (getenv('PHP_CODE_INTEL_DEBUG') === '1') {
        fwrite(STDERR, $e->getTraceAsString() . "\n");
    }
    
    exit(1);
}

__HALT_COMPILER();
STUB;
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

echo "\nBuild complete!\n";