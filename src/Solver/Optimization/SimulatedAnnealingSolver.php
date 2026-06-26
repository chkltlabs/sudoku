<?php

declare(strict_types=1);

namespace Sudoku\Solver\Optimization;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class SimulatedAnnealingSolver extends AbstractSolver
{
    private ?int $seed = null;

    public function withSeed(int $seed): self
    {
        $clone = clone $this;
        $clone->seed = $seed;

        return $clone;
    }

    public function name(): string
    {
        return 'simulated-annealing';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Optimization;
    }

    public function description(): string
    {
        return 'Stochastic solver minimizing row/column/box constraint violations via simulated annealing.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;

        if ($this->seed !== null) {
            mt_srand($this->seed);
        }

        /** @var list<int> $state */
        $state = [];

        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;
            $value = $board->get($row, $col);
            $state[] = $value === 0 ? random_int(1, 9) : $value;
        }

        $best = $state;
        $bestEnergy = ConstraintEnergy::violations($state);
        $current = $state;
        $currentEnergy = $bestEnergy;
        $temperature = 5.0;
        $cooling = 0.9995;
        $maxIterations = 200_000;

        for ($iteration = 0; $iteration < $maxIterations; ++$iteration) {
            ++$this->nodesVisited;

            $index = random_int(0, 80);
            $row = intdiv($index, 9);
            $col = $index % 9;

            if ($board->get($row, $col) !== 0) {
                continue;
            }

            $previous = $current[$index];
            $current[$index] = random_int(1, 9);
            $newEnergy = ConstraintEnergy::violations($current);
            $delta = $newEnergy - $currentEnergy;

            if ($delta < 0 || exp(-$delta / $temperature) > (mt_rand() / mt_getrandmax())) {
                $currentEnergy = $newEnergy;

                if ($newEnergy < $bestEnergy) {
                    $bestEnergy = $newEnergy;
                    $best = $current;
                }
            } else {
                $current[$index] = $previous;
            }

            $temperature *= $cooling;

            if ($bestEnergy === 0) {
                break;
            }
        }

        if ($bestEnergy !== 0) {
            return false;
        }

        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;
            $board->set($row, $col, $best[$index]);
        }

        return true;
    }
}
