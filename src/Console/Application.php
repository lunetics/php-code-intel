<?php

declare(strict_types=1);

namespace CodeIntel\Console;

use CodeIntel\Console\Command\FindUsagesCommand;
use CodeIntel\Console\Command\IndexCommand;
use CodeIntel\Console\Command\VersionCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * PHP Code Intelligence Tool Console Application
 * 
 * Provides command-line interface for finding PHP symbol usages
 * Optimized for integration with Claude Code for accurate refactoring
 */
class Application extends BaseApplication
{
    public const VERSION = '1.0.0';
    public const NAME = 'PHP Code Intelligence Tool';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommands([
            new FindUsagesCommand(),
            new IndexCommand(),
            new VersionCommand(),
        ]);

        // Set default command to find-usages for convenience
        $this->setDefaultCommand('find-usages');
    }

    public function getLongVersion(): string
    {
        return sprintf(
            '%s <info>%s</info> by <comment>Claude Code</comment>',
            $this->getName(),
            $this->getVersion()
        );
    }
}