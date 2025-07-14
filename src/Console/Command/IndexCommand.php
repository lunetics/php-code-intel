<?php

declare(strict_types=1);

namespace CodeIntel\Console\Command;

use CodeIntel\Index\SymbolIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'index',
    description: 'Index PHP files for symbol analysis',
)]
class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Path(s) to index (files or directories)'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Paths to exclude from indexing'
            )
            ->addOption(
                'stats',
                's',
                InputOption::VALUE_NONE,
                'Show indexing statistics'
            )
            ->setHelp(
                <<<'EOF'
The <info>index</info> command indexes PHP files for symbol analysis.

<comment>Examples:</comment>
  <info>php-code-intel index src/</info>
  <info>php-code-intel index src/ tests/ --exclude=vendor</info>
  <info>php-code-intel index . --stats</info>

<comment>Features:</comment>
  - Recursively indexes directories for PHP files
  - Builds symbol table for fast lookups
  - Excludes vendor directories by default
  - Provides detailed statistics with --stats
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Type-safe extraction of command arguments and options
        $pathsRaw = $input->getArgument('paths');
        $excludePathsRaw = $input->getOption('exclude');
        $showStats = $input->getOption('stats');
        
        // Validate and cast paths argument to array of strings
        if (!is_array($pathsRaw)) {
            $io->error('Invalid paths argument - expected array');
            return Command::FAILURE;
        }
        $paths = $this->validateStringArray($pathsRaw, 'paths');
        
        // Validate and cast exclude paths option to array of strings
        $excludePaths = [];
        if ($excludePathsRaw !== null) {
            if (!is_array($excludePathsRaw)) {
                $io->error('Invalid exclude option - expected array');
                return Command::FAILURE;
            }
            $excludePaths = $this->validateStringArray($excludePathsRaw, 'exclude paths');
        }
        
        // Validate showStats option
        if ($showStats !== null && !is_bool($showStats)) {
            $io->error('Invalid stats option - expected boolean');
            return Command::FAILURE;
        }
        
        // Add common exclusions
        $excludePaths = array_merge($excludePaths, ['vendor', 'node_modules', '.git']);
        
        $io->title('PHP Code Intelligence - File Indexing');
        
        if ($output->isVerbose()) {
            $io->section('Configuration');
            $io->definitionList(
                ['Paths' => implode(', ', $paths)],
                ['Exclude' => implode(', ', $excludePaths)]
            );
        }

        try {
            $index = new SymbolIndex();
            $totalFiles = 0;
            $totalSymbols = 0;
            $errors = [];
            
            foreach ($paths as $path) {
                // Type guard: ensure path is a string
                if (!is_string($path)) {
                    $io->warning('Invalid path type - skipping non-string path');
                    continue;
                }
                
                if (!file_exists($path)) {
                    $io->warning("Path does not exist: $path");
                    continue;
                }
                
                if (is_file($path)) {
                    if ($this->shouldIncludeFile($path, $excludePaths)) {
                        $this->indexFileWithStats($index, $path, $totalFiles, $totalSymbols, $errors);
                    }
                } else {
                    $this->indexDirectoryWithStats($index, $path, $excludePaths, $totalFiles, $totalSymbols, $errors);
                }
            }
            
            if ($totalFiles === 0) {
                $io->warning('No PHP files found to index');
                return Command::SUCCESS;
            }

            $io->success("Successfully indexed $totalFiles file(s)");
            
            if ($showStats) {
                $this->displayStats($io, $totalFiles, $totalSymbols, $errors);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred during indexing: ' . $e->getMessage());
            if ($output->isVeryVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
    
    /**
     * @param array<array{file: string, error: string}> $errors
     */
    private function indexFileWithStats(SymbolIndex $index, string $filePath, int &$totalFiles, int &$totalSymbols, array &$errors): void
    {
        try {
            $beforeCount = $index->getSymbolCount();
            $index->indexFile($filePath);
            $afterCount = $index->getSymbolCount();
            
            $totalFiles++;
            $totalSymbols += ($afterCount - $beforeCount);
        } catch (\Exception $e) {
            $errors[] = ['file' => $filePath, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * @param array<string> $excludePaths
     * @param array<array{file: string, error: string}> $errors
     */
    private function indexDirectoryWithStats(SymbolIndex $index, string $directory, array $excludePaths, int &$totalFiles, int &$totalSymbols, array &$errors): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            // Type guard: ensure we have a proper SplFileInfo object
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                $this->shouldIncludeFile($file->getPathname(), $excludePaths)
            ) {
                $this->indexFileWithStats($index, $file->getPathname(), $totalFiles, $totalSymbols, $errors);
            }
        }
    }
    
    /**
     * @param array<string> $excludePaths
     */
    private function shouldIncludeFile(string $filePath, array $excludePaths): bool
    {
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return false;
        }
        
        foreach ($excludePaths as $excludePath) {
            $realExcludePath = realpath($excludePath);
            if ($realExcludePath !== false && str_starts_with($realPath, $realExcludePath)) {
                return false;
            }
            
            // Also check if any parent directory matches exclude pattern
            if (str_contains($realPath, DIRECTORY_SEPARATOR . $excludePath . DIRECTORY_SEPARATOR)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @param array<array{file: string, error: string}> $errors
     */
    private function displayStats(SymfonyStyle $io, int $totalFiles, int $totalSymbols, array $errors): void
    {
        $io->section('Indexing Statistics');
        
        $io->definitionList(
            ['Files Processed' => $totalFiles],
            ['Symbols Found' => $totalSymbols],
            ['Average Symbols/File' => $totalFiles > 0 ? round($totalSymbols / $totalFiles, 1) : 0],
            ['Errors' => count($errors)]
        );
        
        if (!empty($errors)) {
            $io->section('Indexing Errors');
            foreach ($errors as $error) {
                $io->text("<error>•</error> {$error['file']}: {$error['error']}");
            }
        }
        
        // Memory usage
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $io->text(sprintf('Peak memory usage: %.2f MB', $memoryUsage));
    }
    
    /**
     * Validates that all elements in an array are strings
     * 
     * @param array<mixed> $array
     * @return array<string>
     * @throws \InvalidArgumentException
     */
    private function validateStringArray(array $array, string $context): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid %s at index %s - expected string, got %s', 
                        $context, 
                        $key, 
                        get_debug_type($value)
                    )
                );
            }
            $result[] = $value;
        }
        return $result;
    }
}