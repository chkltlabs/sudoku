<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class HiddenSingleTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'hidden-single';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($digit = 1; $digit <= 9; ++$digit) {
            $bit = CandidateGrid::digitBit($digit);

            for ($row = 0; $row < 9; ++$row) {
                if ($this->digitPlacedInRow($grid, $row, $digit)) {
                    continue;
                }

                $cols = [];

                for ($col = 0; $col < 9; ++$col) {
                    if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                        $cols[] = $col;
                    }
                }

                if (count($cols) === 1) {
                    $grid->assign($row, $cols[0], $digit);
                    $progress = true;
                }
            }

            for ($col = 0; $col < 9; ++$col) {
                if ($this->digitPlacedInColumn($grid, $col, $digit)) {
                    continue;
                }

                $rows = [];

                for ($row = 0; $row < 9; ++$row) {
                    if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                        $rows[] = $row;
                    }
                }

                if (count($rows) === 1) {
                    $grid->assign($rows[0], $col, $digit);
                    $progress = true;
                }
            }

            for ($box = 0; $box < 9; ++$box) {
                if ($this->digitPlacedInBox($grid, $box, $digit)) {
                    continue;
                }

                $cells = [];
                $startRow = intdiv($box, 3) * 3;
                $startCol = ($box % 3) * 3;

                for ($row = $startRow; $row < $startRow + 3; ++$row) {
                    for ($col = $startCol; $col < $startCol + 3; ++$col) {
                        if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                            $cells[] = [$row, $col];
                        }
                    }
                }

                if (count($cells) === 1) {
                    [$row, $col] = $cells[0];
                    $grid->assign($row, $col, $digit);
                    $progress = true;
                }
            }
        }

        return $progress;
    }

    private function digitPlacedInRow(CandidateGrid $grid, int $row, int $digit): bool
    {
        for ($col = 0; $col < 9; ++$col) {
            if ($grid->getValue($row, $col) === $digit) {
                return true;
            }
        }

        return false;
    }

    private function digitPlacedInColumn(CandidateGrid $grid, int $col, int $digit): bool
    {
        for ($row = 0; $row < 9; ++$row) {
            if ($grid->getValue($row, $col) === $digit) {
                return true;
            }
        }

        return false;
    }

    private function digitPlacedInBox(CandidateGrid $grid, int $box, int $digit): bool
    {
        $startRow = intdiv($box, 3) * 3;
        $startCol = ($box % 3) * 3;

        for ($row = $startRow; $row < $startRow + 3; ++$row) {
            for ($col = $startCol; $col < $startCol + 3; ++$col) {
                if ($grid->getValue($row, $col) === $digit) {
                    return true;
                }
            }
        }

        return false;
    }
}
