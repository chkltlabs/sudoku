<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

use Sudoku\Core\BoardParser;
use Sudoku\Solver\SolverCategory;
use Sudoku\Solver\SolverMetadataInterface;
use Sudoku\Solver\SolverRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'compare', description: 'Compare registered solvers on a puzzle')]
final class CompareCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to puzzle file')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Filter solvers by category')
            ->addOption('runs', 'r', InputOption::VALUE_REQUIRED, 'Number of runs for stochastic solvers', '1')
            ->addOption('seed', 's', InputOption::VALUE_REQUIRED, 'Random seed for stochastic solvers')
            ->addOption(
                'sort',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated sort keys: solver, category, success, time, nodes, guessing (default: solver)',
                'solver',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parser = new BoardParser();
        $registry = new SolverRegistry();
        $sorter = new CompareRowSorter();

        try {
            $board = $this->loadBoard($parser, $input);
            $category = $this->parseCategory($input->getOption('category'));
            $runs = max(1, (int) $input->getOption('runs'));
            $seed = $input->getOption('seed');
            $seedValue = $seed !== null ? (int) $seed : null;
            $sortOption = $this->normalizeSortOption($input->getOption('sort'));
            $sorter->parseSortKeys($sortOption);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $solvers = $registry->all($category);

        if ($solvers === []) {
            $output->writeln('<error>No solvers match the requested category.</error>');

            return Command::FAILURE;
        }

        $rows = [];

        foreach ($solvers as $solver) {
            $rows[] = $this->benchmarkSolver($solver, $board, $runs, $seedValue);
        }

        $rows = $sorter->sort($rows, $sortOption);

        $table = new Table($output);
        $table->setHeaders(['Solver', 'Category', 'Success', 'Time (ms)', 'Nodes visited', 'Guessing']);

        foreach ($rows as $row) {
            $table->addRow($row->toTableRow());
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function benchmarkSolver(
        \Sudoku\Solver\SolverInterface $solver,
        \Sudoku\Core\Board $board,
        int $runs,
        ?int $seedValue,
    ): CompareResultRow {
        $successes = 0;
        $totalMs = 0.0;
        $totalNodes = 0;
        $lastResult = null;

        for ($run = 0; $run < $runs; ++$run) {
            $solverInstance = $this->prepareSolver($solver, $seedValue, $run);
            $result = $solverInstance->solve($board->clone());
            $lastResult = $result;
            $totalMs += $result->elapsedMs;
            $totalNodes += $result->nodesVisited;

            if ($result->success) {
                ++$successes;
            }
        }

        $categoryLabel = $solver instanceof SolverMetadataInterface
            ? $solver->category()->value
            : 'unknown';

        return new CompareResultRow(
            solver: $solver->name(),
            category: $categoryLabel,
            successDisplay: $runs === 1
                ? ($lastResult?->success ? 'yes' : 'no')
                : sprintf('%d/%d', $successes, $runs),
            successRate: $successes / $runs,
            timeMs: $totalMs / $runs,
            nodesVisited: intdiv($totalNodes, $runs),
            guessingDisplay: $lastResult?->usedGuessing ? 'yes' : 'no',
            guessingSort: $lastResult?->usedGuessing ? 1 : 0,
        );
    }

    private function normalizeSortOption(mixed $sortOption): ?string
    {
        if (!is_string($sortOption)) {
            return null;
        }

        return $sortOption;
    }

    private function prepareSolver(\Sudoku\Solver\SolverInterface $solver, ?int $seed, int $run): \Sudoku\Solver\SolverInterface
    {
        if ($seed === null) {
            return $solver;
        }

        if (method_exists($solver, 'withSeed')) {
            return $solver->withSeed($seed + $run);
        }

        return $solver;
    }

    private function parseCategory(mixed $value): ?SolverCategory
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $category = SolverCategory::tryFrom(strtolower($value));

        if ($category === null) {
            throw new \InvalidArgumentException('Invalid category. Use exact-cover, search, propagation, human-logic, hybrid, optimization, or alternative.');
        }

        return $category;
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
