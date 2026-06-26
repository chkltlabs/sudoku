<?php

declare(strict_types=1);

namespace Sudoku\Cli;

use Sudoku\Cli\Command\BenchCommand;
use Sudoku\Cli\Command\CompareCommand;
use Sudoku\Cli\Command\GenerateCommand;
use Sudoku\Cli\Command\SolveCommand;
use Sudoku\Cli\Command\ValidateCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Sudoku', '1.0.0');

        $this->addCommands([
            new GenerateCommand(),
            new SolveCommand(),
            new ValidateCommand(),
            new CompareCommand(),
            new BenchCommand(),
        ]);
    }
}
