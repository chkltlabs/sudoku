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

#[AsCommand(name: 'bench', description: 'Benchmark solvers against fixture puzzles and output CSV')]
final class BenchCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('fixtures', null, InputOption::VALUE_REQUIRED, 'Fixture directory', 'tests/fixtures')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Filter solvers by category')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixturesDir = (string) $input->getOption('fixtures');
        $registry = new SolverRegistry();
        $parser = new BoardParser();

        try {
            $category = $this->parseCategory($input->getOption('category'));
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $files = glob(rtrim($fixturesDir, '/') . '/*.txt') ?: [];

        if ($files === []) {
            $output->writeln('<error>No fixture files found.</error>');

            return Command::FAILURE;
        }

        $solvers = $registry->all($category);
        $rows = [];
        $rows[] = ['fixture', 'solver', 'category', 'success', 'elapsed_ms', 'nodes_visited', 'used_guessing'];

        foreach ($files as $file) {
            $board = $parser->parseFile($file);
            $fixtureName = basename($file);

            foreach ($solvers as $solver) {
                $result = $solver->solve($board->clone());
                $rows[] = [
                    $fixtureName,
                    $solver->name(),
                    $solver instanceof SolverMetadataInterface ? $solver->category()->value : 'unknown',
                    $result->success ? 'yes' : 'no',
                    sprintf('%.4f', $result->elapsedMs),
                    (string) $result->nodesVisited,
                    $result->usedGuessing ? 'yes' : 'no',
                ];
            }
        }

        $csv = $this->toCsv($rows);
        $outputPath = $input->getOption('output');

        if (is_string($outputPath) && $outputPath !== '') {
            file_put_contents($outputPath, $csv);
            $output->writeln(sprintf('Wrote benchmark CSV to %s', $outputPath));
        } else {
            $output->write($csv);
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<list<string>> $rows
     */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV buffer.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function parseCategory(mixed $value): ?SolverCategory
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $category = SolverCategory::tryFrom(strtolower($value));

        if ($category === null) {
            throw new \InvalidArgumentException('Invalid category.');
        }

        return $category;
    }
}
