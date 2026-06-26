<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class MrvLcvSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'mrv-lcv';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Search;
    }

    public function description(): string
    {
        return 'Bitmask search with minimum-remaining-values and least-constraining-value ordering.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $engine = new BitmaskSearchEngine($this->nodesVisited);

        return $engine->solve($board, useLcv: true, propagateAtEachStep: false);
    }
}
