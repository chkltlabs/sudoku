<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class SwordfishTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'swordfish';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($digit = 1; $digit <= 9; ++$digit) {
            $progress = $this->fish($grid, $digit, true) || $progress;
            $progress = $this->fish($grid, $digit, false) || $progress;
        }

        return $progress;
    }

    private function fish(CandidateGrid $grid, int $digit, bool $rowsArePrimary): bool
    {
        $bit = CandidateGrid::digitBit($digit);
        $linePositions = [];

        for ($line = 0; $line < 9; ++$line) {
            $positions = [];

            for ($cross = 0; $cross < 9; ++$cross) {
                $row = $rowsArePrimary ? $line : $cross;
                $col = $rowsArePrimary ? $cross : $line;

                if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                    $positions[] = $cross;
                }
            }

            if ($positions !== [] && count($positions) <= 3) {
                $linePositions[$line] = array_values(array_unique($positions));
            }
        }

        $keys = array_keys($linePositions);

        for ($i = 0; $i < count($keys); ++$i) {
            for ($j = $i + 1; $j < count($keys); ++$j) {
                for ($k = $j + 1; $k < count($keys); ++$k) {
                    $union = array_values(array_unique(array_merge(
                        $linePositions[$keys[$i]],
                        $linePositions[$keys[$j]],
                        $linePositions[$keys[$k]],
                    )));

                    if (count($union) !== 3) {
                        continue;
                    }

                    if ($this->eliminate($grid, $digit, $keys[$i], $keys[$j], $keys[$k], $union, $rowsArePrimary)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param list<int> $positions
     */
    private function eliminate(
        CandidateGrid $grid,
        int $digit,
        int $lineA,
        int $lineB,
        int $lineC,
        array $positions,
        bool $rowsArePrimary,
    ): bool {
        $progress = false;
        $lines = [$lineA, $lineB, $lineC];

        foreach ($positions as $cross) {
            if ($rowsArePrimary) {
                for ($row = 0; $row < 9; ++$row) {
                    if (in_array($row, $lines, true)) {
                        continue;
                    }

                    if ($grid->eliminate($row, $cross, $digit) === false) {
                        return false;
                    }

                    if ($grid->getValue($row, $cross) === 0
                        && ($grid->getCandidates($row, $cross) & CandidateGrid::digitBit($digit)) === 0
                    ) {
                        $progress = true;
                    }
                }
            } else {
                for ($col = 0; $col < 9; ++$col) {
                    if (in_array($col, $lines, true)) {
                        continue;
                    }

                    if ($grid->eliminate($cross, $col, $digit) === false) {
                        return false;
                    }

                    if ($grid->getValue($cross, $col) === 0
                        && ($grid->getCandidates($cross, $col) & CandidateGrid::digitBit($digit)) === 0
                    ) {
                        $progress = true;
                    }
                }
            }
        }

        return $progress;
    }
}
