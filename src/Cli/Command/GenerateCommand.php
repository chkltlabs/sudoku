<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

use Sudoku\Core\BoardFormatter;
use Sudoku\Generator\Difficulty;
use Sudoku\Generator\PuzzleGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'generate', description: 'Generate a new Sudoku puzzle')]
final class GenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('difficulty', 'd', InputOption::VALUE_REQUIRED, 'Difficulty: easy, medium, or hard', 'medium')
            ->addOption('seed', 's', InputOption::VALUE_REQUIRED, 'Random seed for reproducible puzzles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $difficultyValue = strtolower((string) $input->getOption('difficulty'));
        $difficulty = Difficulty::tryFrom($difficultyValue);

        if ($difficulty === null) {
            $output->writeln('<error>Invalid difficulty. Use easy, medium, or hard.</error>');

            return Command::FAILURE;
        }

        $seedOption = $input->getOption('seed');
        $seed = $seedOption !== null ? (int) $seedOption : null;

        $generator = new PuzzleGenerator();
        $puzzle = $generator->generate($difficulty, $seed);
        $formatter = new BoardFormatter();

        $output->writeln(sprintf('Difficulty: %s (%d clues)', $puzzle->difficulty->value, $puzzle->clueCount));
        $output->writeln($formatter->format($puzzle->board));

        return Command::SUCCESS;
    }
}
