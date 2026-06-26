<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Solver\Alternative\PatternOverlaySolver;
use Sudoku\Solver\Alternative\SatDpllSolver;
use Sudoku\Solver\Hybrid\PropagateThenSearchSolver;
use Sudoku\Solver\Logic\HumanAdvancedSolver;
use Sudoku\Solver\Logic\HumanBasicSolver;
use Sudoku\Solver\Optimization\GeneticAlgorithmSolver;
use Sudoku\Solver\Optimization\SimulatedAnnealingSolver;
use Sudoku\Solver\Search\BitmaskMrvSolver;
use Sudoku\Solver\Search\ForwardCheckingSolver;
use Sudoku\Solver\Search\MrvLcvSolver;
use Sudoku\Solver\Search\NaiveBacktrackingSolver;
use Sudoku\Solver\Search\NorvigFullSolver;
use Sudoku\Solver\Search\NorvigSolver;

final class SolverRegistry
{
    /** @var array<string, SolverInterface> */
    private array $solvers = [];

    public function __construct()
    {
        foreach ($this->defaultSolvers() as $solver) {
            $this->register($solver);
        }
    }

    /**
     * @return list<SolverInterface>
     */
    private function defaultSolvers(): array
    {
        return [
            new ExactCoverSolver(),
            new NaiveBacktrackingSolver(),
            new BitmaskMrvSolver(),
            new ForwardCheckingSolver(),
            new MrvLcvSolver(),
            new NorvigSolver(),
            new NorvigFullSolver(),
            new HumanBasicSolver(),
            new HumanAdvancedSolver(),
            new PropagateThenSearchSolver(),
            new SatDpllSolver(),
            new PatternOverlaySolver(),
            new SimulatedAnnealingSolver(),
            new GeneticAlgorithmSolver(),
        ];
    }

    public function register(SolverInterface $solver): void
    {
        $this->solvers[$solver->name()] = $solver;
    }

    public function get(string $name): SolverInterface
    {
        if (!isset($this->solvers[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown solver: %s', $name));
        }

        return $this->solvers[$name];
    }

    /**
     * @return list<SolverInterface>
     */
    public function all(?SolverCategory $category = null): array
    {
        $solvers = array_values($this->solvers);

        if ($category === null) {
            return $solvers;
        }

        return array_values(array_filter(
            $solvers,
            static fn (SolverInterface $solver): bool => $solver instanceof SolverMetadataInterface
                && $solver->category() === $category,
        ));
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->solvers);
    }
}
