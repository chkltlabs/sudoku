<?php

declare(strict_types=1);

namespace Sudoku\Solver\Hybrid;

use Sudoku\Core\Board;
use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\Logic\Techniques\HiddenPairTechnique;
use Sudoku\Solver\Logic\Techniques\HiddenSingleTechnique;
use Sudoku\Solver\Logic\Techniques\NakedPairTechnique;
use Sudoku\Solver\Logic\Techniques\NakedSingleTechnique;
use Sudoku\Solver\Logic\Techniques\PointingPairTechnique;
use Sudoku\Solver\Search\BitmaskSearchEngine;
use Sudoku\Solver\SolverCategory;

final class PropagateThenSearchSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'propagate-then-search';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Hybrid;
    }

    public function description(): string
    {
        return 'Apply human-basic logic propagation, then bitmask MRV search on the remainder.';
    }

    protected function solveInternal(Board $board): bool
    {
        $grid = CandidateGrid::fromBoard($board);
        $techniques = [
            new NakedSingleTechnique(),
            new HiddenSingleTechnique(),
            new PointingPairTechnique(),
            new NakedPairTechnique(),
            new HiddenPairTechnique(),
        ];

        $changed = true;

        while ($changed) {
            $changed = false;

            foreach ($techniques as $technique) {
                if ($technique->apply($grid)) {
                    $this->recordTechnique($technique->name());
                    $changed = true;
                }

                if ($grid->hasContradiction()) {
                    return false;
                }
            }
        }

        $grid->copyToBoard($board);

        if ($grid->isComplete()) {
            return true;
        }

        $this->usedGuessing = true;
        $engine = new BitmaskSearchEngine($this->nodesVisited);

        return $engine->solve($board, useLcv: false, propagateAtEachStep: false);
    }
}
