<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class BitmaskSearchEngine
{
    public function __construct(
        private int &$nodesVisited,
    ) {
    }

    public function solve(Board $board, bool $useLcv = false, bool $propagateAtEachStep = true): bool
    {
        $grid = CandidateGrid::fromBoard($board);

        if ($grid->hasContradiction()) {
            return false;
        }

        if ($propagateAtEachStep && !$grid->propagateAll()) {
            return false;
        }

        $solved = $this->search($grid, $useLcv, $propagateAtEachStep);

        if ($solved) {
            $grid->copyToBoard($board);
        }

        return $solved;
    }

    private function search(CandidateGrid $grid, bool $useLcv, bool $propagateAtEachStep): bool
    {
        ++$this->nodesVisited;

        if ($propagateAtEachStep && !$grid->propagateAll()) {
            return false;
        }

        if ($grid->isComplete()) {
            return true;
        }

        $cell = $grid->findMrvCell();

        if ($cell === null || $cell['mask'] === 0) {
            return false;
        }

        $digits = $useLcv
            ? $grid->orderByLcv($cell['row'], $cell['col'], $cell['mask'])
            : CandidateGrid::digitsFromMask($cell['mask']);

        foreach ($digits as $digit) {
            $snapshot = $grid->snapshot();

            if ($grid->assign($cell['row'], $cell['col'], $digit) && $this->search($grid, $useLcv, $propagateAtEachStep)) {
                return true;
            }

            $grid->restore($snapshot);
        }

        return false;
    }
}
