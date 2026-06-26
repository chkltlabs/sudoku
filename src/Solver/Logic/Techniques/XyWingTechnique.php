<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class XyWingTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'xy-wing';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;
        $pivots = [];

        for ($row = 0; $row < 9; ++$row) {
            for ($col = 0; $col < 9; ++$col) {
                if ($grid->getValue($row, $col) !== 0) {
                    continue;
                }

                $mask = $grid->getCandidates($row, $col);

                if (CandidateGrid::popcount($mask) === 2) {
                    $pivots[] = [$row, $col, $mask];
                }
            }
        }

        foreach ($pivots as [$pRow, $pCol, $pMask]) {
            $pDigits = CandidateGrid::digitsFromMask($pMask);

            for ($row = 0; $row < 9; ++$row) {
                for ($col = 0; $col < 9; ++$col) {
                    if ($grid->getValue($row, $col) !== 0) {
                        continue;
                    }

                    $mask = $grid->getCandidates($row, $col);

                    if (CandidateGrid::popcount($mask) !== 2) {
                        continue;
                    }

                    if (!$this->arePeers($pRow, $pCol, $row, $col)) {
                        continue;
                    }

                    $shared = $pMask & $mask;

                    if ($shared === 0) {
                        continue;
                    }

                    $zDigit = 0;

                    foreach ($pDigits as $digit) {
                        if (($mask & CandidateGrid::digitBit($digit)) === 0) {
                            $zDigit = $digit;
                            break;
                        }
                    }

                    if ($zDigit === 0) {
                        continue;
                    }

                    for ($row2 = 0; $row2 < 9; ++$row2) {
                        for ($col2 = 0; $col2 < 9; ++$col2) {
                            if (($row2 === $row && $col2 === $col) || ($row2 === $pRow && $col2 === $pCol)) {
                                continue;
                            }

                            if ($grid->getValue($row2, $col2) !== 0) {
                                continue;
                            }

                            $mask2 = $grid->getCandidates($row2, $col2);

                            if (CandidateGrid::popcount($mask2) !== 2) {
                                continue;
                            }

                            if (!$this->arePeers($pRow, $pCol, $row2, $col2)) {
                                continue;
                            }

                            if ($this->arePeers($row, $col, $row2, $col2)) {
                                continue;
                            }

                            if (($mask2 & CandidateGrid::digitBit($zDigit)) === 0) {
                                continue;
                            }

                            $otherDigit = 0;

                            foreach (CandidateGrid::digitsFromMask($mask2) as $digit) {
                                if ($digit !== $zDigit) {
                                    $otherDigit = $digit;
                                }
                            }

                            if (($pMask & CandidateGrid::digitBit($otherDigit)) === 0) {
                                continue;
                            }

                            for ($targetRow = 0; $targetRow < 9; ++$targetRow) {
                                for ($targetCol = 0; $targetCol < 9; ++$targetCol) {
                                    if ($grid->getValue($targetRow, $targetCol) !== 0) {
                                        continue;
                                    }

                                    if (!$this->arePeers($row, $col, $targetRow, $targetCol)
                                        && !$this->arePeers($row2, $col2, $targetRow, $targetCol)
                                    ) {
                                        continue;
                                    }

                                    if (($grid->getCandidates($targetRow, $targetCol) & CandidateGrid::digitBit($zDigit)) !== 0) {
                                        $grid->eliminate($targetRow, $targetCol, $zDigit);
                                        $progress = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $progress;
    }

    private function arePeers(int $rowA, int $colA, int $rowB, int $colB): bool
    {
        if ($rowA === $rowB && $colA === $colB) {
            return false;
        }

        if ($rowA === $rowB || $colA === $colB) {
            return true;
        }

        return intdiv($rowA, 3) === intdiv($rowB, 3) && intdiv($colA, 3) === intdiv($colB, 3);
    }
}
