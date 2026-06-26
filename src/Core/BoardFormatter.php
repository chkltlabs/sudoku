<?php

declare(strict_types=1);

namespace Sudoku\Core;

final class BoardFormatter
{
    public function format(Board $board): string
    {
        $lines = [];
        $lines[] = '+-------+-------+-------+';

        for ($row = 0; $row < Board::SIZE; ++$row) {
            $cells = [];

            for ($col = 0; $col < Board::SIZE; ++$col) {
                $value = $board->get($row, $col);
                $cells[] = $value === 0 ? '.' : (string) $value;
            }

            $lines[] = sprintf(
                '| %s %s %s | %s %s %s | %s %s %s |',
                $cells[0],
                $cells[1],
                $cells[2],
                $cells[3],
                $cells[4],
                $cells[5],
                $cells[6],
                $cells[7],
                $cells[8],
            );

            if ($row === 2 || $row === 5) {
                $lines[] = '+-------+-------+-------+';
            }
        }

        $lines[] = '+-------+-------+-------+';

        return implode(PHP_EOL, $lines);
    }
}
