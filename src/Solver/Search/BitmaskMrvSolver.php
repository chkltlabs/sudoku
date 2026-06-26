<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class BitmaskMrvSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'bitmask-mrv';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Search;
    }

    public function description(): string
    {
        return 'Bitmask candidate sets with minimum-remaining-values variable ordering.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $engine = new BitmaskSearchEngine($this->nodesVisited);

        return $engine->solve($board, useLcv: false, propagateAtEachStep: false);
    }
}
