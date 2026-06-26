<?php

declare(strict_types=1);

namespace Sudoku\Core;

final readonly class Coordinate
{
    public function __construct(
        public int $row,
        public int $col,
    ) {
        if ($row < 0 || $row > 8 || $col < 0 || $col > 8) {
            throw new \InvalidArgumentException(sprintf('Coordinate out of bounds: (%d, %d)', $row, $col));
        }
    }

    public static function fromIndex(int $index): self
    {
        if ($index < 0 || $index > 80) {
            throw new \InvalidArgumentException(sprintf('Index out of bounds: %d', $index));
        }

        return new self(intdiv($index, 9), $index % 9);
    }

    public function toIndex(): int
    {
        return $this->row * 9 + $this->col;
    }

    public function box(): int
    {
        return intdiv($this->row, 3) * 3 + intdiv($this->col, 3);
    }
}
