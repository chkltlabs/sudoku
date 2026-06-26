<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class XWingTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'x-wing';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($digit = 1; $digit <= 9; ++$digit) {
            $progress = $this->fishInRows($grid, $digit) || $progress;
            $progress = $this->fishInCols($grid, $digit) || $progress;
        }

        return $progress;
    }

    private function fishInRows(CandidateGrid $grid, int $digit): bool
    {
        $bit = CandidateGrid::digitBit($digit);
        $rowCols = [];

        for ($row = 0; $row < 9; ++$row) {
            $cols = [];

            for ($col = 0; $col < 9; ++$col) {
                if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                    $cols[] = $col;
                }
            }

            if (count($cols) === 2) {
                $rowCols[$row] = $cols;
            }
        }

        return $this->eliminateFish($grid, $digit, $rowCols, true);
    }

    private function fishInCols(CandidateGrid $grid, int $digit): bool
    {
        $bit = CandidateGrid::digitBit($digit);
        $colRows = [];

        for ($col = 0; $col < 9; ++$col) {
            $rows = [];

            for ($row = 0; $row < 9; ++$row) {
                if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                    $rows[] = $row;
                }
            }

            if (count($rows) === 2) {
                $colRows[$col] = $rows;
            }
        }

        return $this->eliminateFish($grid, $digit, $colRows, false);
    }

    /**
     * @param array<int, list<int>> $primaryLines
     */
    private function eliminateFish(CandidateGrid $grid, int $digit, array $primaryLines, bool $rowsArePrimary): bool
    {
        $progress = false;
        $keys = array_keys($primaryLines);

        for ($i = 0; $i < count($keys); ++$i) {
            for ($j = $i + 1; $j < count($keys); ++$j) {
                $lineA = $keys[$i];
                $lineB = $keys[$j];

                if ($primaryLines[$lineA] !== $primaryLines[$lineB]) {
                    continue;
                }

                foreach ($primaryLines[$lineA] as $secondaryLine) {
                    if ($rowsArePrimary) {
                        for ($row = 0; $row < 9; ++$row) {
                            if ($row === $lineA || $row === $lineB) {
                                continue;
                            }

                            if ($grid->eliminate($row, $secondaryLine, $digit) === false) {
                                return false;
                            }

                            if ($grid->getValue($row, $secondaryLine) === 0
                                && ($grid->getCandidates($row, $secondaryLine) & CandidateGrid::digitBit($digit)) === 0
                            ) {
                                $progress = true;
                            }
                        }
                    } else {
                        for ($col = 0; $col < 9; ++$col) {
                            if ($col === $lineA || $col === $lineB) {
                                continue;
                            }

                            if ($grid->eliminate($secondaryLine, $col, $digit) === false) {
                                return false;
                            }

                            if ($grid->getValue($secondaryLine, $col) === 0
                                && ($grid->getCandidates($secondaryLine, $col) & CandidateGrid::digitBit($digit)) === 0
                            ) {
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
