<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Core\Board;

abstract class AbstractSolver implements SolverInterface, SolverMetadataInterface
{
    protected int $nodesVisited = 0;

    protected bool $usedGuessing = false;

    /** @var list<string> */
    protected array $techniquesApplied = [];

    public function solve(Board $board): SolverResult
    {
        $this->nodesVisited = 0;
        $this->usedGuessing = false;
        $this->techniquesApplied = [];

        $start = hrtime(true);
        $working = $board->clone();
        $success = $this->solveInternal($working);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        return new SolverResult(
            success: $success,
            solution: $success ? $working : null,
            elapsedMs: $elapsedMs,
            nodesVisited: $this->nodesVisited,
            message: $success ? $this->successMessage() : $this->failureMessage(),
            usedGuessing: $this->usedGuessing,
            techniquesApplied: $this->techniquesApplied,
        );
    }

    abstract protected function solveInternal(Board $board): bool;

    protected function successMessage(): string
    {
        return sprintf('Solved with %s.', $this->name());
    }

    protected function failureMessage(): string
    {
        return sprintf('%s could not solve the puzzle.', $this->name());
    }

    protected function recordTechnique(string $technique): void
    {
        if (!in_array($technique, $this->techniquesApplied, true)) {
            $this->techniquesApplied[] = $technique;
        }
    }
}
