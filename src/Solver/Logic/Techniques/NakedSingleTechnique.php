<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class NakedSingleTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'naked-single';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($row = 0; $row < 9; ++$row) {
            for ($col = 0; $col < 9; ++$col) {
                if ($grid->getValue($row, $col) !== 0) {
                    continue;
                }

                $mask = $grid->getCandidates($row, $col);

                if (CandidateGrid::popcount($mask) === 1) {
                    $digit = CandidateGrid::digitFromBit($mask);
                    $grid->assign($row, $col, $digit);
                    $progress = true;
                }
            }
        }

        return $progress;
    }
}
