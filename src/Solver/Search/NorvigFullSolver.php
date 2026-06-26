<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class NorvigFullSolver extends AbstractSolver
{
    public function name(): string
    {
        return 'norvig-full';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Hybrid;
    }

    public function description(): string
    {
        return 'Arc consistency, naked-pair elimination, dual consistency, and MRV search.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $grid = CandidateGrid::fromBoard($board);

        if ($grid->hasContradiction()) {
            return false;
        }

        $solved = $this->search($grid);

        if ($solved) {
            $grid->copyToBoard($board);
        }

        return $solved;
    }

    private function search(CandidateGrid $grid): bool
    {
        ++$this->nodesVisited;

        if (!$this->propagateExtended($grid)) {
            return false;
        }

        if ($grid->isComplete()) {
            return true;
        }

        $cell = $grid->findMrvCell();

        if ($cell === null || $cell['mask'] === 0) {
            return false;
        }

        foreach (CandidateGrid::digitsFromMask($cell['mask']) as $digit) {
            $snapshot = $grid->snapshot();

            if ($grid->assign($cell['row'], $cell['col'], $digit) && $this->search($grid)) {
                return true;
            }

            $grid->restore($snapshot);
        }

        return false;
    }

    private function propagateExtended(CandidateGrid $grid): bool
    {
        $changed = true;

        while ($changed) {
            $changed = false;

            if (!$grid->propagateAll()) {
                return false;
            }

            if ($this->applyNakedPairs($grid)) {
                $changed = true;
            }
        }

        return !$grid->hasContradiction();
    }

    private function applyNakedPairs(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($row = 0; $row < 9; ++$row) {
            $progress = $this->nakedPairsInUnit($grid, 'row', $row) || $progress;
        }

        for ($col = 0; $col < 9; ++$col) {
            $progress = $this->nakedPairsInUnit($grid, 'col', $col) || $progress;
        }

        for ($box = 0; $box < 9; ++$box) {
            $progress = $this->nakedPairsInUnit($grid, 'box', $box) || $progress;
        }

        return $progress;
    }

    private function nakedPairsInUnit(CandidateGrid $grid, string $unitType, int $index): bool
    {
        $cells = $this->unitCells($unitType, $index);
        $progress = false;

        for ($i = 0; $i < count($cells); ++$i) {
            [$rowA, $colA] = $cells[$i];

            if ($grid->getValue($rowA, $colA) !== 0) {
                continue;
            }

            $maskA = $grid->getCandidates($rowA, $colA);

            if (CandidateGrid::popcount($maskA) !== 2) {
                continue;
            }

            for ($j = $i + 1; $j < count($cells); ++$j) {
                [$rowB, $colB] = $cells[$j];

                if ($grid->getValue($rowB, $colB) !== 0) {
                    continue;
                }

                if ($grid->getCandidates($rowB, $colB) !== $maskA) {
                    continue;
                }

                foreach ($cells as [$row, $col]) {
                    if (($row === $rowA && $col === $colA) || ($row === $rowB && $col === $colB)) {
                        continue;
                    }

                    if ($grid->getValue($row, $col) !== 0) {
                        continue;
                    }

                    $before = $grid->getCandidates($row, $col);
                    $afterMask = $before & ~$maskA;

                    if ($afterMask !== $before) {
                        foreach (CandidateGrid::digitsFromMask($maskA) as $digit) {
                            $grid->eliminate($row, $col, $digit);
                        }

                        $progress = true;
                    }
                }
            }
        }

        return $progress;
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
