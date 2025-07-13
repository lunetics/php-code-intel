<?php

declare(strict_types=1);

namespace CodeIntel\Console\Command;

use CodeIntel\Finder\UsageFinder;
use CodeIntel\Index\SymbolIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'find-usages',
    description: 'Find all usages of a PHP symbol (class, method, etc.)',
)]
class FindUsagesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'symbol',
                InputArgument::REQUIRED,
                'The fully qualified symbol name to search for (e.g., "App\\User" or "App\\User::getName")'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path(s) to search in (files or directories)',
                ['.']
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: json, table, claude',
                'claude'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Paths to exclude from search'
            )
            ->addOption(
                'confidence',
                'c',
                InputOption::VALUE_REQUIRED,
                'Minimum confidence level: CERTAIN, PROBABLE, POSSIBLE, DYNAMIC',
                'POSSIBLE'
            )
            ->setHelp(
                <<<'EOF'
The <info>find-usages</info> command finds all usages of a PHP symbol in your codebase.

<comment>Examples:</comment>
  <info>php-code-intel find-usages "App\User"</info>
  <info>php-code-intel find-usages "App\User::getName" --path=src</info>
  <info>php-code-intel find-usages "MyClass" --format=json</info>
  <info>php-code-intel find-usages "Service::method" --confidence=CERTAIN</info>

<comment>Supported symbol types:</comment>
  - Classes: App\User, MyNamespace\MyClass
  - Methods: App\User::getName, Service::processData
  - Static methods: Math::calculate, Helper::format

<comment>Output formats:</comment>
  - <info>claude</info>: Optimized for Claude Code integration (default)
  - <info>json</info>: Machine-readable JSON format
  - <info>table</info>: Human-readable table format

<comment>Confidence levels:</comment>
  - <info>CERTAIN</info>: Direct usage (new Class(), Class::method())
  - <info>PROBABLE</info>: Type-hinted usage (?->, method chaining)
  - <info>POSSIBLE</info>: Dynamic usage (new $class, $obj->$method)
  - <info>DYNAMIC</info>: Magic usage (call_user_func, __call)
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $symbol = $input->getArgument('symbol');
        $paths = $input->getOption('path');
        $format = $input->getOption('format');
        $excludePaths = $input->getOption('exclude');
        $minConfidence = $input->getOption('confidence');
        
        // Validate confidence level
        $validConfidences = ['CERTAIN', 'PROBABLE', 'POSSIBLE', 'DYNAMIC'];
        if (!in_array($minConfidence, $validConfidences)) {
            $io->error("Invalid confidence level: $minConfidence. Valid options: " . implode(', ', $validConfidences));
            return Command::FAILURE;
        }

        // Validate output format
        $validFormats = ['json', 'table', 'claude'];
        if (!in_array($format, $validFormats)) {
            $io->error("Invalid format: $format. Valid options: " . implode(', ', $validFormats));
            return Command::FAILURE;
        }

        if ($output->isVerbose()) {
            $io->section('Configuration');
            $io->definitionList(
                ['Symbol' => $symbol],
                ['Paths' => implode(', ', $paths)],
                ['Format' => $format],
                ['Min Confidence' => $minConfidence],
                ['Exclude' => $excludePaths ? implode(', ', $excludePaths) : 'none']
            );
        }

        try {
            // Index files
            $index = new SymbolIndex();
            $finder = new UsageFinder($index);
            
            $totalFiles = $this->indexFiles($index, $paths, $excludePaths, $io);
            
            if ($totalFiles === 0) {
                $io->warning('No PHP files found to index');
                return Command::SUCCESS;
            }

            if ($output->isVerbose()) {
                $io->success("Indexed $totalFiles files");
            }

            // Find usages
            $usages = $finder->find($symbol);
            
            // Filter by confidence if needed
            $usages = $this->filterByConfidence($usages, $minConfidence);

            if (empty($usages)) {
                if ($format === 'claude') {
                    $io->note("No usages found for symbol: $symbol");
                } else {
                    $output->writeln($this->formatOutput($usages, $format));
                }
                return Command::SUCCESS;
            }

            // Output results
            $output->writeln($this->formatOutput($usages, $format));
            
            if ($output->isVerbose()) {
                $io->success(sprintf('Found %d usage(s) of "%s"', count($usages), $symbol));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            if ($output->isVeryVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function indexFiles(SymbolIndex $index, array $paths, array $excludePaths, SymfonyStyle $io): int
    {
        $totalFiles = 0;
        
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $io->warning("Path does not exist: $path");
                continue;
            }
            
            if (is_file($path)) {
                if ($this->shouldIncludeFile($path, $excludePaths)) {
                    $index->indexFile($path);
                    $totalFiles++;
                }
            } else {
                $totalFiles += $this->indexDirectory($index, $path, $excludePaths);
            }
        }
        
        return $totalFiles;
    }
    
    private function indexDirectory(SymbolIndex $index, string $directory, array $excludePaths): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                $this->shouldIncludeFile($file->getPathname(), $excludePaths)
            ) {
                $index->indexFile($file->getPathname());
                $count++;
            }
        }
        
        return $count;
    }
    
    private function shouldIncludeFile(string $filePath, array $excludePaths): bool
    {
        $realPath = realpath($filePath);
        
        foreach ($excludePaths as $excludePath) {
            $realExcludePath = realpath($excludePath);
            if ($realExcludePath && str_starts_with($realPath, $realExcludePath)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function filterByConfidence(array $usages, string $minConfidence): array
    {
        $confidenceOrder = ['DYNAMIC' => 0, 'POSSIBLE' => 1, 'PROBABLE' => 2, 'CERTAIN' => 3];
        $minLevel = $confidenceOrder[$minConfidence];
        
        return array_filter($usages, function ($usage) use ($confidenceOrder, $minLevel) {
            $level = $confidenceOrder[$usage['confidence']] ?? 0;
            return $level >= $minLevel;
        });
    }
    
    private function formatOutput(array $usages, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($usages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                
            case 'table':
                return $this->formatAsTable($usages);
                
            case 'claude':
            default:
                return $this->formatForClaude($usages);
        }
    }
    
    private function formatAsTable(array $usages): string
    {
        if (empty($usages)) {
            return "No usages found.\n";
        }
        
        $output = sprintf("Found %d usage(s):\n\n", count($usages));
        $output .= str_pad('File', 40) . str_pad('Line', 8) . str_pad('Confidence', 12) . "Code\n";
        $output .= str_repeat('-', 100) . "\n";
        
        foreach ($usages as $usage) {
            $file = basename($usage['file']);
            $output .= sprintf(
                "%s %s %s %s\n",
                str_pad($file, 39),
                str_pad((string)$usage['line'], 7),
                str_pad($usage['confidence'], 11),
                trim($usage['code'])
            );
        }
        
        return $output;
    }
    
    private function formatForClaude(array $usages): string
    {
        if (empty($usages)) {
            return "No symbol usages found.";
        }
        
        $output = sprintf("Found %d usage(s):\n\n", count($usages));
        
        foreach ($usages as $usage) {
            $output .= sprintf(
                "%s:%d\n",
                $usage['file'],
                $usage['line']
            );
            $output .= sprintf("  %s (confidence: %s)\n", trim($usage['code']), $usage['confidence']);
            
            if (!empty($usage['context']['lines'])) {
                $output .= "  Context:\n";
                foreach ($usage['context']['lines'] as $i => $line) {
                    $lineNum = $usage['context']['start'] + $i;
                    $marker = $lineNum === $usage['line'] ? '>' : ' ';
                    $output .= sprintf("  %s %d: %s\n", $marker, $lineNum, trim($line));
                }
            }
            $output .= "\n";
        }
        
        return $output;
    }
}