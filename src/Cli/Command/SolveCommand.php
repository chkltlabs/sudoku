<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

use Sudoku\Core\Board;
use Sudoku\Core\BoardFormatter;
use Sudoku\Core\BoardParser;
use Sudoku\Generator\Difficulty;
use Sudoku\Generator\PuzzleGenerator;
use Sudoku\Solver\SolverRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'solve', description: 'Solve a Sudoku puzzle')]
final class SolveCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('solver', null, InputOption::VALUE_REQUIRED, 'Solver to use', 'exact-cover')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to puzzle file')
            ->addOption('generate', 'g', InputOption::VALUE_REQUIRED, 'Generate a puzzle to solve: easy, medium, or hard')
            ->addOption('seed', 's', InputOption::VALUE_REQUIRED, 'Random seed when using --generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parser = new BoardParser();
        $formatter = new BoardFormatter();
        $registry = new SolverRegistry();

        try {
            $board = $this->loadBoard($parser, $input, $output, $formatter);
            $solver = $registry->get((string) $input->getOption('solver'));
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $result = $solver->solve($board);

        if (!$result->success || $result->solution === null) {
            $output->writeln('<error>' . $result->message . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            'Solver: %s | Time: %.2f ms | Nodes visited: %d',
            $solver->name(),
            $result->elapsedMs,
            $result->nodesVisited,
        ));
        $output->writeln($formatter->format($result->solution));

        return Command::SUCCESS;
    }

    private function loadBoard(
        BoardParser $parser,
        InputInterface $input,
        OutputInterface $output,
        BoardFormatter $formatter,
    ): Board {
        $generate = $input->getOption('generate');

        if (is_string($generate) && $generate !== '') {
            return $this->generateBoard($generate, $input, $output, $formatter);
        }

        $file = $input->getOption('file');

        if (is_string($file) && $file !== '') {
            return $parser->parseFile($file);
        }

        if (!$input->isInteractive() && !stream_isatty(STDIN)) {
            return $parser->parseStdin();
        }

        throw new \InvalidArgumentException('Provide a puzzle via --generate, --file, or stdin pipe.');
    }

    private function generateBoard(
        string $difficultyValue,
        InputInterface $input,
        OutputInterface $output,
        BoardFormatter $formatter,
    ): Board {
        $difficulty = Difficulty::tryFrom(strtolower($difficultyValue));

        if ($difficulty === null) {
            throw new \InvalidArgumentException('Invalid difficulty for --generate. Use easy, medium, or hard.');
        }

        $seed = $this->resolveSeed($input);
        $generator = new PuzzleGenerator();
        $puzzle = $generator->generate($difficulty, $seed);

        $output->writeln(sprintf('Generated puzzle: %s (%d clues, seed %d)', $puzzle->difficulty->value, $puzzle->clueCount, $seed));
        $output->writeln($formatter->format($puzzle->board));
        $output->writeln('');

        return $puzzle->board;
    }

    private function resolveSeed(InputInterface $input): int
    {
        $seedOption = $input->getOption('seed');

        if ($seedOption !== null && $seedOption !== '') {
            return (int) $seedOption;
        }

        return random_int(0, PHP_INT_MAX);
    }
}
