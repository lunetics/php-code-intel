<?php

declare(strict_types=1);

namespace CodeIntel\Console\Command;

use CodeIntel\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'version',
    description: 'Show version and system information',
)]
class VersionCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('PHP Code Intelligence Tool');
        
        $io->definitionList(
            ['Version' => Application::VERSION],
            ['PHP Version' => PHP_VERSION],
            ['PHP Parser' => $this->getComposerPackageVersion('nikic/php-parser')],
            ['Symfony Console' => $this->getComposerPackageVersion('symfony/console')],
            ['Memory Limit' => ini_get('memory_limit') ?: 'Unknown'],
            ['Max Execution Time' => (ini_get('max_execution_time') ?: '0') . 's']
        );
        
        $io->section('Capabilities');
        $io->listing([
            'AST-based PHP symbol parsing',
            'Advanced confidence scoring',
            'Nullsafe operator support (?->)',
            'Dynamic method call detection', 
            'Inheritance pattern analysis',
            'Multiple output formats (JSON, Table, Claude)',
            'Parallel processing support',
            'Memory-optimized indexing'
        ]);
        
        $io->section('System Requirements');
        $io->listing([
            'PHP 8.0+ (Current: ' . PHP_VERSION . ')',
            'nikic/php-parser 4.18+',
            'symfony/console 6.4+',
            'Memory: 128MB+ recommended'
        ]);
        
        if ($output->isVerbose()) {
            $this->showDetailedInfo($io);
        }
        
        return Command::SUCCESS;
    }
    
    private function getComposerPackageVersion(string $package): string
    {
        $lockFile = __DIR__ . '/../../../composer.lock';
        
        if (!file_exists($lockFile)) {
            return 'Unknown';
        }
        
        try {
            $content = file_get_contents($lockFile);
            if ($content === false) {
                return 'Unknown';
            }
            $lock = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            // Type validation: ensure we have an array with expected structure
            if (!is_array($lock)) {
                return 'Unknown';
            }
            
            // Search in main packages
            if (isset($lock['packages']) && is_array($lock['packages'])) {
                foreach ($lock['packages'] as $pkg) {
                    if (is_array($pkg) && 
                        isset($pkg['name']) && 
                        $pkg['name'] === $package && 
                        isset($pkg['version']) && 
                        is_string($pkg['version'])) {
                        return $pkg['version'];
                    }
                }
            }
            
            // Search in dev packages
            if (isset($lock['packages-dev']) && is_array($lock['packages-dev'])) {
                foreach ($lock['packages-dev'] as $pkg) {
                    if (is_array($pkg) && 
                        isset($pkg['name']) && 
                        $pkg['name'] === $package && 
                        isset($pkg['version']) && 
                        is_string($pkg['version'])) {
                        return $pkg['version'];
                    }
                }
            }
        } catch (\Exception) {
            return 'Unknown';
        }
        
        return 'Not found';
    }
    
    private function showDetailedInfo(SymfonyStyle $io): void
    {
        $io->section('Detailed System Information');
        
        $io->definitionList(
            ['Operating System' => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')'],
            ['Architecture' => php_uname('m')],
            ['SAPI' => PHP_SAPI],
            ['Zend Engine' => zend_version()],
            ['Extensions' => $this->getRelevantExtensions()],
            ['Include Path' => get_include_path()],
            ['Working Directory' => getcwd() ?: 'Unknown'],
            ['Peak Memory Usage' => $this->formatBytes(memory_get_peak_usage(true))]
        );
        
        $io->section('PHP Configuration');
        $phpConfig = [
            'error_reporting' => $this->getErrorReportingLevel(),
            'display_errors' => (ini_get('display_errors') !== false && ini_get('display_errors') !== '' && ini_get('display_errors') !== '0') ? 'On' : 'Off',
            'log_errors' => (ini_get('log_errors') !== false && ini_get('log_errors') !== '' && ini_get('log_errors') !== '0') ? 'On' : 'Off',
            'short_open_tag' => (ini_get('short_open_tag') !== false && ini_get('short_open_tag') !== '' && ini_get('short_open_tag') !== '0') ? 'On' : 'Off',
            'opcache.enable' => extension_loaded('opcache') && (ini_get('opcache.enable') !== false && ini_get('opcache.enable') !== '' && ini_get('opcache.enable') !== '0') ? 'On' : 'Off'
        ];
        
        foreach ($phpConfig as $key => $value) {
            $io->text("<info>$key:</info> $value");
        }
    }
    
    private function getRelevantExtensions(): string
    {
        $relevant = ['json', 'mbstring', 'tokenizer', 'pcre', 'spl', 'reflection'];
        $loaded = [];
        
        foreach ($relevant as $ext) {
            if (extension_loaded($ext)) {
                $loaded[] = $ext;
            }
        }
        
        return implode(', ', $loaded);
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function getErrorReportingLevel(): string
    {
        $level = error_reporting();
        
        if ($level === E_ALL) {
            return 'E_ALL';
        }
        
        if ($level === (E_ALL & ~E_NOTICE)) {
            return 'E_ALL & ~E_NOTICE';
        }
        
        if ($level === (E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED)) {
            return 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED';
        }
        
        return (string) $level;
    }
}