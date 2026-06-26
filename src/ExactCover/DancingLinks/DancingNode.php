<?php

declare(strict_types=1);

namespace Sudoku\ExactCover\DancingLinks;

class DancingNode
{
    public DancingNode $left;

    public DancingNode $right;

    public DancingNode $up;

    public DancingNode $down;

    public ?ColumnNode $column = null;

    public readonly int $rowId;

    public function __construct(int $rowId = 0)
    {
        $this->rowId = $rowId;
        $this->left = $this;
        $this->right = $this;
        $this->up = $this;
        $this->down = $this;
    }
}
