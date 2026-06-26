<?php

declare(strict_types=1);

namespace Sudoku\Generator;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function targetClues(): int
    {
        return match ($this) {
            self::Easy => 40,
            self::Medium => 34,
            self::Hard => 28,
        };
    }

    public function minimumClues(): int
    {
        return match ($this) {
            self::Easy => 36,
            self::Medium => 30,
            self::Hard => 24,
        };
    }
}
