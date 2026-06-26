<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

use Sudoku\Core\BoardParser;
use Sudoku\Core\SudokuValidator;
use Sudoku\Solver\ExactCoverSolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'validate', description: 'Validate a Sudoku puzzle')]
final class ValidateCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to puzzle file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parser = new BoardParser();
        $validator = new SudokuValidator();
        $solver = new ExactCoverSolver();

        try {
            $board = $this->loadBoard($parser, $input);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $isValid = $validator->isValid($board);
        $isComplete = $board->isComplete();
        $countResult = $solver->countSolutions($board, 2);
        $solutionCount = $countResult['count'];

        $output->writeln(sprintf('Valid layout: %s', $isValid ? 'yes' : 'no'));
        $output->writeln(sprintf('Complete: %s', $isComplete ? 'yes' : 'no'));
        $output->writeln(sprintf('Clues: %d', $board->clueCount()));

        if (!$isValid) {
            $output->writeln('<error>Puzzle violates Sudoku constraints.</error>');

            return Command::FAILURE;
        }

        $status = match (true) {
            $solutionCount === 0 => 'unsolvable',
            $solutionCount === 1 => 'unique solution',
            default => 'multiple solutions',
        };

        $output->writeln(sprintf('Solution status: %s', $status));

        return $solutionCount === 1 ? Command::SUCCESS : Command::FAILURE;
    }

    private function loadBoard(BoardParser $parser, InputInterface $input): \Sudoku\Core\Board
    {
        $file = $input->getOption('file');

        if (is_string($file) && $file !== '') {
            return $parser->parseFile($file);
        }

        if (!$input->isInteractive() && !stream_isatty(STDIN)) {
            return $parser->parseStdin();
        }

        throw new \InvalidArgumentException('Provide a puzzle via --file or stdin pipe.');
    }
}
