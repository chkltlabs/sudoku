<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class PointingPairTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'pointing-pair';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($box = 0; $box < 9; ++$box) {
            $startRow = intdiv($box, 3) * 3;
            $startCol = ($box % 3) * 3;

            for ($digit = 1; $digit <= 9; ++$digit) {
                $bit = CandidateGrid::digitBit($digit);
                $rows = [];
                $cols = [];

                for ($row = $startRow; $row < $startRow + 3; ++$row) {
                    for ($col = $startCol; $col < $startCol + 3; ++$col) {
                        if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                            $rows[$row] = true;
                            $cols[$col] = true;
                        }
                    }
                }

                if (count($rows) === 1) {
                    $row = array_key_first($rows);

                    for ($col = 0; $col < 9; ++$col) {
                        if ($col < $startCol || $col >= $startCol + 3) {
                            if ($grid->eliminate($row, $col, $digit) === false) {
                                return false;
                            }

                            if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) === 0) {
                                $progress = true;
                            }
                        }
                    }
                }

                if (count($cols) === 1) {
                    $col = array_key_first($cols);

                    for ($row = 0; $row < 9; ++$row) {
                        if ($row < $startRow || $row >= $startRow + 3) {
                            if ($grid->eliminate($row, $col, $digit) === false) {
                                return false;
                            }

                            if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) === 0) {
                                $progress = true;
                            }
                        }
                    }
                }
            }
        }

        return $progress;
    }
}
