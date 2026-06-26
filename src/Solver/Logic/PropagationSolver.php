<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic;

use Sudoku\Core\Board;
use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

abstract class PropagationSolver extends AbstractSolver
{
    /** @var list<TechniqueInterface> */
    private array $techniques;

    /**
     * @param list<TechniqueInterface> $techniques
     */
    public function __construct(array $techniques)
    {
        $this->techniques = $techniques;
    }

    public function category(): SolverCategory
    {
        return SolverCategory::HumanLogic;
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = false;
        $grid = CandidateGrid::fromBoard($board);

        if ($grid->hasContradiction()) {
            return false;
        }

        $changed = true;

        while ($changed) {
            $changed = false;

            foreach ($this->techniques as $technique) {
                if ($technique->apply($grid)) {
                    $this->recordTechnique($technique->name());
                    $changed = true;
                }

                if ($grid->hasContradiction()) {
                    return false;
                }
            }
        }

        if (!$grid->isComplete()) {
            return false;
        }

        $grid->copyToBoard($board);

        return true;
    }
}
