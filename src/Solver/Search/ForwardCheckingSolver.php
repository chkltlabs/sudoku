<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class ForwardCheckingSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'forward-checking';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Search;
    }

    public function description(): string
    {
        return 'Depth-first search with candidate-set forward checking on each assignment.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $grid = CandidateGrid::fromBoard($board);

        if ($grid->hasContradiction()) {
            return false;
        }

        $solved = $this->search($grid);

        if ($solved) {
            $grid->copyToBoard($board);
        }

        return $solved;
    }

    private function search(CandidateGrid $grid): bool
    {
        ++$this->nodesVisited;

        if ($grid->isComplete()) {
            return true;
        }

        $cell = $grid->findMrvCell();

        if ($cell === null || $cell['mask'] === 0) {
            return false;
        }

        foreach (CandidateGrid::digitsFromMask($cell['mask']) as $digit) {
            $snapshot = $grid->snapshot();

            if ($grid->assign($cell['row'], $cell['col'], $digit) && $this->search($grid)) {
                return true;
            }

            $grid->restore($snapshot);
        }

        return false;
    }
}
