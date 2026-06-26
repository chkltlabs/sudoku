<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class NorvigSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'norvig';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Search;
    }

    public function description(): string
    {
        return 'Arc-consistency propagation with minimum-remaining-values search (Norvig Algorithm 4 + 7).';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $engine = new BitmaskSearchEngine($this->nodesVisited);

        return $engine->solve($board, useLcv: false, propagateAtEachStep: true);
    }
}
