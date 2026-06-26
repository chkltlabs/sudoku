<?php

declare(strict_types=1);

namespace Sudoku\Solver\Optimization;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class GeneticAlgorithmSolver extends AbstractSolver
{
    private const int POPULATION_SIZE = 40;
    private const int GENERATIONS = 300;

    private ?int $seed = null;

    public function withSeed(int $seed): self
    {
        $clone = clone $this;
        $clone->seed = $seed;

        return $clone;
    }

    public function name(): string
    {
        return 'genetic-algorithm';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Optimization;
    }

    public function description(): string
    {
        return 'Population-based stochastic search minimizing Sudoku constraint violations.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;

        if ($this->seed !== null) {
            mt_srand($this->seed);
        }

        $population = [];

        for ($i = 0; $i < self::POPULATION_SIZE; ++$i) {
            $population[] = $this->randomIndividual($board);
        }

        for ($generation = 0; $generation < self::GENERATIONS; ++$generation) {
            ++$this->nodesVisited;

            usort($population, fn (array $a, array $b): int => $this->fitness($a) <=> $this->fitness($b));

            if ($this->fitness($population[0]) === 0) {
                $this->applyState($board, $population[0]);

                return true;
            }

            $next = array_slice($population, 0, 4);

            while (count($next) < self::POPULATION_SIZE) {
                $parentA = $population[random_int(0, 9)];
                $parentB = $population[random_int(0, 9)];
                $child = $this->crossover($parentA, $parentB, $board);
                $child = $this->mutate($child, $board);
                $next[] = $child;
            }

            $population = $next;
        }

        usort($population, fn (array $a, array $b): int => $this->fitness($a) <=> $this->fitness($b));

        if ($this->fitness($population[0]) !== 0) {
            return false;
        }

        $this->applyState($board, $population[0]);

        return true;
    }

    /**
     * @return list<int>
     */
    private function randomIndividual(Board $board): array
    {
        $state = [];

        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;
            $value = $board->get($row, $col);
            $state[] = $value === 0 ? random_int(1, 9) : $value;
        }

        return $state;
    }

    /**
     * @param list<int> $state
     */
    private function fitness(array $state): int
    {
        return ConstraintEnergy::violations($state);
    }

    /**
     * @param list<int> $parentA
     * @param list<int> $parentB
     * @return list<int>
     */
    private function crossover(array $parentA, array $parentB, Board $board): array
    {
        $child = [];

        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;

            if ($board->get($row, $col) !== 0) {
                $child[] = $board->get($row, $col);
            } else {
                $child[] = random_int(0, 1) === 0 ? $parentA[$index] : $parentB[$index];
            }
        }

        return $child;
    }

    /**
     * @param list<int> $state
     * @return list<int>
     */
    private function mutate(array $state, Board $board): array
    {
        if (random_int(0, 100) > 20) {
            return $state;
        }

        $index = random_int(0, 80);
        $row = intdiv($index, 9);
        $col = $index % 9;

        if ($board->get($row, $col) !== 0) {
            return $state;
        }

        $state[$index] = random_int(1, 9);

        return $state;
    }

    /**
     * @param list<int> $state
     */
    private function applyState(Board $board, array $state): void
    {
        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;
            $board->set($row, $col, $state[$index]);
        }
    }
}
