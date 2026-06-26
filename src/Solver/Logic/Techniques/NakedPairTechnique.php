<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;

final class NakedPairTechnique extends SubsetTechnique
{
    public function name(): string
    {
        return 'naked-pair';
    }

    public function apply(CandidateGrid $grid): bool
    {
        return $this->nakedSubset($grid, 2);
    }
}
