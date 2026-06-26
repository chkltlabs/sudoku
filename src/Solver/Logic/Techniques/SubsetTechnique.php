<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

abstract class SubsetTechnique implements TechniqueInterface
{
    protected function nakedSubset(CandidateGrid $grid, int $size): bool
    {
        $progress = false;

        for ($row = 0; $row < 9; ++$row) {
            $progress = $this->nakedSubsetInUnit($grid, 'row', $row, $size) || $progress;
        }

        for ($col = 0; $col < 9; ++$col) {
            $progress = $this->nakedSubsetInUnit($grid, 'col', $col, $size) || $progress;
        }

        for ($box = 0; $box < 9; ++$box) {
            $progress = $this->nakedSubsetInUnit($grid, 'box', $box, $size) || $progress;
        }

        return $progress;
    }

    protected function hiddenSubset(CandidateGrid $grid, int $size): bool
    {
        $progress = false;

        for ($row = 0; $row < 9; ++$row) {
            $progress = $this->hiddenSubsetInUnit($grid, 'row', $row, $size) || $progress;
        }

        for ($col = 0; $col < 9; ++$col) {
            $progress = $this->hiddenSubsetInUnit($grid, 'col', $col, $size) || $progress;
        }

        for ($box = 0; $box < 9; ++$box) {
            $progress = $this->hiddenSubsetInUnit($grid, 'box', $box, $size) || $progress;
        }

        return $progress;
    }

    private function nakedSubsetInUnit(CandidateGrid $grid, string $unitType, int $index, int $size): bool
    {
        $cells = $this->unitCells($unitType, $index);
        $progress = false;
        $emptyCells = [];

        foreach ($cells as [$row, $col]) {
            if ($grid->getValue($row, $col) === 0) {
                $emptyCells[] = [$row, $col, $grid->getCandidates($row, $col)];
            }
        }

        $count = count($emptyCells);

        if ($count < $size) {
            return false;
        }

        $combinations = $this->combinations($emptyCells, $size);

        foreach ($combinations as $combo) {
            $union = 0;

            foreach ($combo as [, , $mask]) {
                $union |= $mask;
            }

            if (CandidateGrid::popcount($union) !== $size) {
                continue;
            }

            foreach ($emptyCells as [$row, $col, $mask]) {
                $inCombo = false;

                foreach ($combo as [$cRow, $cCol]) {
                    if ($cRow === $row && $cCol === $col) {
                        $inCombo = true;
                        break;
                    }
                }

                if ($inCombo || $grid->getValue($row, $col) !== 0) {
                    continue;
                }

                $before = $grid->getCandidates($row, $col);
                $after = $before & ~$union;

                if ($after !== $before) {
                    foreach (CandidateGrid::digitsFromMask($union) as $digit) {
                        $grid->eliminate($row, $col, $digit);
                    }

                    $progress = true;
                }
            }
        }

        return $progress;
    }

    private function hiddenSubsetInUnit(CandidateGrid $grid, string $unitType, int $index, int $size): bool
    {
        $cells = $this->unitCells($unitType, $index);
        $progress = false;

        for ($digitMask = 1; $digitMask < (1 << 9); ++$digitMask) {
            if (CandidateGrid::popcount($digitMask) !== $size) {
                continue;
            }

            $locations = [];

            foreach ($cells as [$row, $col]) {
                if ($grid->getValue($row, $col) !== 0) {
                    continue;
                }

                if (($grid->getCandidates($row, $col) & $digitMask) !== 0) {
                    $locations[] = [$row, $col];
                }
            }

            if (count($locations) !== $size) {
                continue;
            }

            foreach ($locations as [$row, $col]) {
                $before = $grid->getCandidates($row, $col);
                $after = $before & $digitMask;

                if ($after !== $before) {
                    for ($digit = 1; $digit <= 9; ++$digit) {
                        $bit = CandidateGrid::digitBit($digit);

                        if (($before & $bit) !== 0 && ($after & $bit) === 0) {
                            $grid->eliminate($row, $col, $digit);
                        }
                    }

                    $progress = true;
                }
            }
        }

        return $progress;
    }

    /**
     * @param list<array{int, int, int}> $cells
     * @return list<list<array{int, int, int}>>
     */
    private function combinations(array $cells, int $size): array
    {
        $result = [];
        $this->combine($cells, $size, 0, [], $result);

        return $result;
    }

    /**
     * @param list<array{int, int, int}> $cells
     * @param list<array{int, int, int}> $current
     * @param list<list<array{int, int, int}>> $result
     */
    private function combine(array $cells, int $size, int $start, array $current, array &$result): void
    {
        if (count($current) === $size) {
            $result[] = $current;

            return;
        }

        for ($i = $start; $i < count($cells); ++$i) {
            $current[] = $cells[$i];
            $this->combine($cells, $size, $i + 1, $current, $result);
            array_pop($current);
        }
    }

    /**
     * @return list<array{int, int}>
     */
    private function unitCells(string $unitType, int $index): array
    {
        $cells = [];

        if ($unitType === 'row') {
            for ($col = 0; $col < 9; ++$col) {
                $cells[] = [$index, $col];
            }
        } elseif ($unitType === 'col') {
            for ($row = 0; $row < 9; ++$row) {
                $cells[] = [$row, $index];
            }
        } else {
            $startRow = intdiv($index, 3) * 3;
            $startCol = ($index % 3) * 3;

            for ($row = $startRow; $row < $startRow + 3; ++$row) {
                for ($col = $startCol; $col < $startCol + 3; ++$col) {
                    $cells[] = [$row, $col];
                }
            }
        }

        return $cells;
    }
}
