<?php

declare(strict_types=1);

namespace Sudoku\Core;

final class SudokuValidator
{
    public function isValid(Board $board): bool
    {
        for ($row = 0; $row < Board::SIZE; ++$row) {
            if (!$this->hasUniqueNonZero($this->rowValues($board, $row))) {
                return false;
            }
        }

        for ($col = 0; $col < Board::SIZE; ++$col) {
            if (!$this->hasUniqueNonZero($this->columnValues($board, $col))) {
                return false;
            }
        }

        for ($box = 0; $box < Board::SIZE; ++$box) {
            if (!$this->hasUniqueNonZero($this->boxValues($board, $box))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<int>
     */
    private function rowValues(Board $board, int $row): array
    {
        $values = [];

        for ($col = 0; $col < Board::SIZE; ++$col) {
            $values[] = $board->get($row, $col);
        }

        return $values;
    }

    /**
     * @return list<int>
     */
    private function columnValues(Board $board, int $col): array
    {
        $values = [];

        for ($row = 0; $row < Board::SIZE; ++$row) {
            $values[] = $board->get($row, $col);
        }

        return $values;
    }

    /**
     * @return list<int>
     */
    private function boxValues(Board $board, int $box): array
    {
        $startRow = intdiv($box, 3) * 3;
        $startCol = ($box % 3) * 3;
        $values = [];

        for ($row = $startRow; $row < $startRow + 3; ++$row) {
            for ($col = $startCol; $col < $startCol + 3; ++$col) {
                $values[] = $board->get($row, $col);
            }
        }

        return $values;
    }

    /**
     * @param list<int> $values
     */
    private function hasUniqueNonZero(array $values): bool
    {
        $seen = [];

        foreach ($values as $value) {
            if ($value === 0) {
                continue;
            }

            if (isset($seen[$value])) {
                return false;
            }

            $seen[$value] = true;
        }

        return true;
    }
}
