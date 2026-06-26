<?php

declare(strict_types=1);

namespace Sudoku\Core;

final class CandidateGrid
{
    public const int ALL_DIGITS = 0x1FF;

    /** @var array<int, array<int, int>> */
    private array $values;

    /** @var array<int, array<int, int>> */
    private array $candidates;

    /** @var array<int, int> */
    private array $rowMask;

    /** @var array<int, int> */
    private array $colMask;

    /** @var array<int, int> */
    private array $boxMask;

    private function __construct()
    {
        $this->values = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, 0));
        $this->candidates = array_fill(0, Board::SIZE, array_fill(0, Board::SIZE, 0));
        $this->rowMask = array_fill(0, Board::SIZE, 0);
        $this->colMask = array_fill(0, Board::SIZE, 0);
        $this->boxMask = array_fill(0, Board::SIZE, 0);
    }

    public static function fromBoard(Board $board): self
    {
        $grid = new self();

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                $value = $board->get($row, $col);

                if ($value === 0) {
                    continue;
                }

                $bit = self::digitBit($value);
                $box = $grid->boxIndex($row, $col);

                if (($grid->rowMask[$row] & $bit) !== 0
                    || ($grid->colMask[$col] & $bit) !== 0
                    || ($grid->boxMask[$box] & $bit) !== 0
                ) {
                    return $grid;
                }

                $grid->values[$row][$col] = $value;
                $grid->candidates[$row][$col] = $bit;
                $grid->rowMask[$row] |= $bit;
                $grid->colMask[$col] |= $bit;
                $grid->boxMask[$box] |= $bit;
            }
        }

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                if ($grid->values[$row][$col] !== 0) {
                    continue;
                }

                $grid->candidates[$row][$col] = self::ALL_DIGITS
                    & ~$grid->rowMask[$row]
                    & ~$grid->colMask[$col]
                    & ~$grid->boxMask[$grid->boxIndex($row, $col)];
            }
        }

        return $grid;
    }

    public function toBoard(): Board
    {
        return new Board($this->values);
    }

    public function copyToBoard(Board $board): void
    {
        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                $board->set($row, $col, $this->values[$row][$col]);
            }
        }
    }

    public function getValue(int $row, int $col): int
    {
        return $this->values[$row][$col];
    }

    public function getCandidates(int $row, int $col): int
    {
        return $this->candidates[$row][$col];
    }

    public function isComplete(): bool
    {
        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                if ($this->values[$row][$col] === 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function hasContradiction(): bool
    {
        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                if ($this->values[$row][$col] === 0 && $this->candidates[$row][$col] === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function assign(int $row, int $col, int $digit): bool
    {
        if ($this->values[$row][$col] !== 0) {
            return $this->values[$row][$col] === $digit;
        }

        $bit = self::digitBit($digit);

        if (($this->candidates[$row][$col] & $bit) === 0) {
            return false;
        }

        return $this->place($row, $col, $digit);
    }

    public function eliminate(int $row, int $col, int $digit): bool
    {
        if ($this->values[$row][$col] !== 0) {
            return true;
        }

        $bit = self::digitBit($digit);

        if (($this->candidates[$row][$col] & $bit) === 0) {
            return true;
        }

        $this->candidates[$row][$col] &= ~$bit;

        return $this->candidates[$row][$col] !== 0;
    }

    public function propagateSingles(): bool
    {
        $changed = true;

        while ($changed) {
            $changed = false;

            for ($row = 0; $row < Board::SIZE; ++$row) {
                for ($col = 0; $col < Board::SIZE; ++$col) {
                    if ($this->values[$row][$col] !== 0) {
                        continue;
                    }

                    $mask = $this->candidates[$row][$col];

                    if ($mask === 0) {
                        return false;
                    }

                    if (self::popcount($mask) === 1) {
                        $digit = self::digitFromBit($mask);

                        if (!$this->place($row, $col, $digit)) {
                            return false;
                        }

                        $changed = true;
                    }
                }
            }

            for ($digit = 1; $digit <= Board::SIZE; ++$digit) {
                $bit = self::digitBit($digit);

                for ($row = 0; $row < Board::SIZE; ++$row) {
                    if (($this->rowMask[$row] & $bit) !== 0) {
                        continue;
                    }

                    $cols = [];

                    for ($col = 0; $col < Board::SIZE; ++$col) {
                        if ($this->values[$row][$col] === 0 && ($this->candidates[$row][$col] & $bit) !== 0) {
                            $cols[] = $col;
                        }
                    }

                    if ($cols === []) {
                        return false;
                    }

                    if (count($cols) === 1) {
                        if (!$this->place($row, $cols[0], $digit)) {
                            return false;
                        }

                        $changed = true;
                    }
                }

                for ($col = 0; $col < Board::SIZE; ++$col) {
                    if (($this->colMask[$col] & $bit) !== 0) {
                        continue;
                    }

                    $rows = [];

                    for ($row = 0; $row < Board::SIZE; ++$row) {
                        if ($this->values[$row][$col] === 0 && ($this->candidates[$row][$col] & $bit) !== 0) {
                            $rows[] = $row;
                        }
                    }

                    if ($rows === []) {
                        return false;
                    }

                    if (count($rows) === 1) {
                        if (!$this->place($rows[0], $col, $digit)) {
                            return false;
                        }

                        $changed = true;
                    }
                }

                for ($box = 0; $box < Board::SIZE; ++$box) {
                    if (($this->boxMask[$box] & $bit) !== 0) {
                        continue;
                    }

                    $cells = [];

                    for ($offset = 0; $offset < Board::SIZE; ++$offset) {
                        $row = intdiv($box, 3) * 3 + intdiv($offset, 3);
                        $col = ($box % 3) * 3 + ($offset % 3);

                        if ($this->values[$row][$col] === 0 && ($this->candidates[$row][$col] & $bit) !== 0) {
                            $cells[] = [$row, $col];
                        }
                    }

                    if ($cells === []) {
                        return false;
                    }

                    if (count($cells) === 1) {
                        [$row, $col] = $cells[0];

                        if (!$this->place($row, $col, $digit)) {
                            return false;
                        }

                        $changed = true;
                    }
                }
            }
        }

        return !$this->hasContradiction();
    }

    public function propagateAll(): bool
    {
        return $this->propagateSingles();
    }

    /**
     * @return array{row: int, col: int, mask: int}|null
     */
    public function findMrvCell(): ?array
    {
        $best = null;
        $bestCount = 10;

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                if ($this->values[$row][$col] !== 0) {
                    continue;
                }

                $count = self::popcount($this->candidates[$row][$col]);

                if ($count < $bestCount) {
                    $bestCount = $count;
                    $best = ['row' => $row, 'col' => $col, 'mask' => $this->candidates[$row][$col]];

                    if ($bestCount === 1) {
                        return $best;
                    }
                }
            }
        }

        return $best;
    }

    /**
     * @return list<int>
     */
    public function orderByLcv(int $row, int $col, int $mask): array
    {
        $scores = [];

        for ($digit = 1; $digit <= Board::SIZE; ++$digit) {
            $bit = self::digitBit($digit);

            if (($mask & $bit) === 0) {
                continue;
            }

            $eliminations = 0;

            for ($peerCol = 0; $peerCol < Board::SIZE; ++$peerCol) {
                if ($peerCol !== $col && $this->values[$row][$peerCol] === 0 && ($this->candidates[$row][$peerCol] & $bit) !== 0) {
                    ++$eliminations;
                }
            }

            for ($peerRow = 0; $peerRow < Board::SIZE; ++$peerRow) {
                if ($peerRow !== $row && $this->values[$peerRow][$col] === 0 && ($this->candidates[$peerRow][$col] & $bit) !== 0) {
                    ++$eliminations;
                }
            }

            $box = $this->boxIndex($row, $col);
            $startRow = intdiv($box, 3) * 3;
            $startCol = ($box % 3) * 3;

            for ($peerRow = $startRow; $peerRow < $startRow + 3; ++$peerRow) {
                for ($peerCol = $startCol; $peerCol < $startCol + 3; ++$peerCol) {
                    if (($peerRow !== $row || $peerCol !== $col)
                        && $this->values[$peerRow][$peerCol] === 0
                        && ($this->candidates[$peerRow][$peerCol] & $bit) !== 0
                    ) {
                        ++$eliminations;
                    }
                }
            }

            $scores[$digit] = $eliminations;
        }

        asort($scores);

        return array_keys($scores);
    }

    /**
     * @return list<int>
     */
    public static function digitsFromMask(int $mask): array
    {
        $digits = [];

        for ($digit = 1; $digit <= Board::SIZE; ++$digit) {
            if (($mask & self::digitBit($digit)) !== 0) {
                $digits[] = $digit;
            }
        }

        return $digits;
    }

    public function snapshot(): string
    {
        return serialize([
            $this->values,
            $this->candidates,
            $this->rowMask,
            $this->colMask,
            $this->boxMask,
        ]);
    }

    public function restore(string $snapshot): void
    {
        [$values, $candidates, $rowMask, $colMask, $boxMask] = unserialize($snapshot, ['allowed_classes' => false]);
        $this->values = $values;
        $this->candidates = $candidates;
        $this->rowMask = $rowMask;
        $this->colMask = $colMask;
        $this->boxMask = $boxMask;
    }

    public function clone(): self
    {
        $copy = new self();
        $copy->values = $this->values;
        $copy->candidates = $this->candidates;
        $copy->rowMask = $this->rowMask;
        $copy->colMask = $this->colMask;
        $copy->boxMask = $this->boxMask;

        return $copy;
    }

    public static function digitBit(int $digit): int
    {
        return 1 << ($digit - 1);
    }

    public static function digitFromBit(int $bit): int
    {
        return (int) log($bit, 2) + 1;
    }

    public static function popcount(int $mask): int
    {
        return substr_count(decbin($mask), '1');
    }

    private function place(int $row, int $col, int $digit): bool
    {
        $bit = self::digitBit($digit);
        $box = $this->boxIndex($row, $col);

        if (($this->rowMask[$row] & $bit) !== 0
            || ($this->colMask[$col] & $bit) !== 0
            || ($this->boxMask[$box] & $bit) !== 0
        ) {
            return false;
        }

        $this->values[$row][$col] = $digit;
        $this->candidates[$row][$col] = $bit;
        $this->rowMask[$row] |= $bit;
        $this->colMask[$col] |= $bit;
        $this->boxMask[$box] |= $bit;

        for ($peerCol = 0; $peerCol < Board::SIZE; ++$peerCol) {
            if ($peerCol !== $col && $this->values[$row][$peerCol] === 0) {
                $this->candidates[$row][$peerCol] &= ~$bit;
            }
        }

        for ($peerRow = 0; $peerRow < Board::SIZE; ++$peerRow) {
            if ($peerRow !== $row && $this->values[$peerRow][$col] === 0) {
                $this->candidates[$peerRow][$col] &= ~$bit;
            }
        }

        $startRow = intdiv($box, 3) * 3;
        $startCol = ($box % 3) * 3;

        for ($peerRow = $startRow; $peerRow < $startRow + 3; ++$peerRow) {
            for ($peerCol = $startCol; $peerCol < $startCol + 3; ++$peerCol) {
                if (($peerRow !== $row || $peerCol !== $col) && $this->values[$peerRow][$peerCol] === 0) {
                    $this->candidates[$peerRow][$peerCol] &= ~$bit;
                }
            }
        }

        return !$this->hasContradiction();
    }

    private function boxIndex(int $row, int $col): int
    {
        return intdiv($row, 3) * 3 + intdiv($col, 3);
    }
}
