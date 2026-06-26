<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;

final class HiddenPairTechnique extends SubsetTechnique
{
    public function name(): string
    {
        return 'hidden-pair';
    }

    public function apply(CandidateGrid $grid): bool
    {
        return $this->hiddenSubset($grid, 2);
    }
}
