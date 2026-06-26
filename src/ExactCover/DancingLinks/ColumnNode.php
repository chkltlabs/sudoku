<?php

declare(strict_types=1);

namespace Sudoku\ExactCover\DancingLinks;

final class ColumnNode extends DancingNode
{
    public int $size = 0;

    public readonly int $index;

    public readonly string $name;

    public function __construct(int $index, string $name = '')
    {
        parent::__construct(-1);
        $this->index = $index;
        $this->name = $name;
    }
}
