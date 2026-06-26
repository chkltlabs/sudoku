<?php

declare(strict_types=1);

namespace Sudoku\Solver\Alternative;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class PatternOverlaySolver extends AbstractSolver
{
    public function name(): string
    {
        return 'pattern-overlay';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Alternative;
    }

    public function description(): string
    {
        return 'Row-wise permutation overlay search using valid digit distribution patterns.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;

        return $this->searchRows($board, 0);
    }

    private function searchRows(Board $board, int $row): bool
    {
        ++$this->nodesVisited;

        if ($row === Board::SIZE) {
            return true;
        }

        $fixed = [];

        for ($col = 0; $col < Board::SIZE; ++$col) {
            $value = $board->get($row, $col);

            if ($value !== 0) {
                $fixed[$col] = $value;
            }
        }

        $pattern = range(1, 9);

        do {
            $valid = true;

            foreach ($fixed as $col => $value) {
                if ($pattern[$col] !== $value) {
                    $valid = false;
                    break;
                }
            }

            if ($valid) {
                $snapshot = [];

                for ($col = 0; $col < Board::SIZE; ++$col) {
                    $snapshot[$col] = $board->get($row, $col);
                    $board->set($row, $col, $pattern[$col]);
                }

                if ($this->rowIsValid($board, $row) && $this->searchRows($board, $row + 1)) {
                    return true;
                }

                for ($col = 0; $col < Board::SIZE; ++$col) {
                    $board->set($row, $col, $snapshot[$col]);
                }
            }
        } while ($this->nextPermutation($pattern));

        return false;
    }

    private function rowIsValid(Board $board, int $row): bool
    {
        for ($col = 0; $col < Board::SIZE; ++$col) {
            $value = $board->get($row, $col);
            $box = intdiv($row, 3) * 3 + intdiv($col, 3);
            $startRow = intdiv($box, 3) * 3;
            $startCol = ($box % 3) * 3;

            for ($peerRow = $startRow; $peerRow < $startRow + 3; ++$peerRow) {
                if ($peerRow === $row) {
                    continue;
                }

                for ($peerCol = $startCol; $peerCol < $startCol + 3; ++$peerCol) {
                    if ($board->get($peerRow, $peerCol) === $value) {
                        return false;
                    }
                }
            }
        }

        for ($col = 0; $col < Board::SIZE; ++$col) {
            $value = $board->get($row, $col);

            for ($peerRow = 0; $peerRow < $row; ++$peerRow) {
                if ($board->get($peerRow, $col) === $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param list<int> $input
     */
    private function nextPermutation(array &$input): bool
    {
        $n = count($input);
        $i = $n - 2;

        while ($i >= 0 && $input[$i] >= $input[$i + 1]) {
            --$i;
        }

        if ($i < 0) {
            return false;
        }

        $j = $n - 1;

        while ($input[$j] <= $input[$i]) {
            --$j;
        }

        [$input[$i], $input[$j]] = [$input[$j], $input[$i]];

        $left = $i + 1;
        $right = $n - 1;

        while ($left < $right) {
            [$input[$left], $input[$right]] = [$input[$right], $input[$left]];
            ++$left;
            --$right;
        }

        return true;
    }
}
