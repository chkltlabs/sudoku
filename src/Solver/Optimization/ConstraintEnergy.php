<?php

declare(strict_types=1);

namespace Sudoku\Solver\Optimization;

final class ConstraintEnergy
{
    /**
     * @param list<int> $state
     */
    public static function violations(array $state): int
    {
        $violations = 0;

        for ($row = 0; $row < 9; ++$row) {
            $violations += 9 - count(array_unique(array_slice($state, $row * 9, 9)));
        }

        for ($col = 0; $col < 9; ++$col) {
            $values = [];

            for ($row = 0; $row < 9; ++$row) {
                $values[] = $state[$row * 9 + $col];
            }

            $violations += 9 - count(array_unique($values));
        }

        for ($box = 0; $box < 9; ++$box) {
            $values = [];
            $startRow = intdiv($box, 3) * 3;
            $startCol = ($box % 3) * 3;

            for ($row = $startRow; $row < $startRow + 3; ++$row) {
                for ($col = $startCol; $col < $startCol + 3; ++$col) {
                    $values[] = $state[$row * 9 + $col];
                }
            }

            $violations += 9 - count(array_unique($values));
        }

        return $violations;
    }
}
