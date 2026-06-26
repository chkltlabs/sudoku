<?php

declare(strict_types=1);

namespace Sudoku\Core;

final class Board
{
    public const int SIZE = 9;

    /** @var array<int, array<int, int>> */
    private array $cells;

    /**
     * @param array<int, array<int, int>>|null $cells
     */
    public function __construct(?array $cells = null)
    {
        if ($cells === null) {
            $this->cells = array_fill(0, self::SIZE, array_fill(0, self::SIZE, 0));

            return;
        }

        if (count($cells) !== self::SIZE) {
            throw new \InvalidArgumentException('Board must have 9 rows.');
        }

        foreach ($cells as $rowIndex => $row) {
            if (count($row) !== self::SIZE) {
                throw new \InvalidArgumentException(sprintf('Row %d must have 9 columns.', $rowIndex));
            }

            foreach ($row as $value) {
                if (!is_int($value) || $value < 0 || $value > 9) {
                    throw new \InvalidArgumentException('Cell values must be integers from 0 to 9.');
                }
            }
        }

        $this->cells = $cells;
    }

    public function get(int $row, int $col): int
    {
        return $this->cells[$row][$col];
    }

    public function set(int $row, int $col, int $value): void
    {
        if ($value < 0 || $value > 9) {
            throw new \InvalidArgumentException('Cell value must be between 0 and 9.');
        }

        $this->cells[$row][$col] = $value;
    }

    public function isEmpty(int $row, int $col): bool
    {
        return $this->cells[$row][$col] === 0;
    }

    public function isComplete(): bool
    {
        for ($row = 0; $row < self::SIZE; ++$row) {
            for ($col = 0; $col < self::SIZE; ++$col) {
                if ($this->cells[$row][$col] === 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function clueCount(): int
    {
        $count = 0;

        for ($row = 0; $row < self::SIZE; ++$row) {
            for ($col = 0; $col < self::SIZE; ++$col) {
                if ($this->cells[$row][$col] !== 0) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * @return array<int, array<int, int>>
     */
    public function toArray(): array
    {
        return $this->cells;
    }

    public function toFlatString(): string
    {
        $flat = '';

        for ($row = 0; $row < self::SIZE; ++$row) {
            for ($col = 0; $col < self::SIZE; ++$col) {
                $value = $this->cells[$row][$col];
                $flat .= $value === 0 ? '.' : (string) $value;
            }
        }

        return $flat;
    }

    public function clone(): self
    {
        $copy = [];

        for ($row = 0; $row < self::SIZE; ++$row) {
            $copy[$row] = $this->cells[$row];
        }

        return new self($copy);
    }

    public function equals(self $other): bool
    {
        for ($row = 0; $row < self::SIZE; ++$row) {
            for ($col = 0; $col < self::SIZE; ++$col) {
                if ($this->cells[$row][$col] !== $other->cells[$row][$col]) {
                    return false;
                }
            }
        }

        return true;
    }
}
