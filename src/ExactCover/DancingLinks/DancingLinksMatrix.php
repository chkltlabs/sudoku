<?php

declare(strict_types=1);

namespace Sudoku\ExactCover\DancingLinks;

final class DancingLinksMatrix
{
    private readonly ColumnNode $header;

    /** @var array<int, DancingNode> */
    private array $rowNodes = [];

    public function __construct(int $columnCount)
    {
        $this->header = new ColumnNode(-1, 'header');

        $previous = $this->header;

        for ($index = 0; $index < $columnCount; ++$index) {
            $column = new ColumnNode($index, (string) $index);
            $column->left = $previous;
            $previous->right = $column;
            $this->header->left = $column;
            $column->right = $this->header;
            $previous = $column;
        }
    }

    /**
     * @param list<int> $columns
     */
    public function addRow(int $rowId, array $columns): void
    {
        if ($columns === []) {
            return;
        }

        $firstNode = null;
        $previousNode = null;

        foreach ($columns as $columnIndex) {
            $column = $this->getColumn($columnIndex);
            $node = new DancingNode($rowId);
            $node->column = $column;

            $node->down = $column;
            $node->up = $column->up;
            $column->up->down = $node;
            $column->up = $node;

            if ($firstNode === null) {
                $firstNode = $node;
                $previousNode = $node;
            } else {
                $node->right = $firstNode;
                $node->left = $previousNode;
                $previousNode->right = $node;
                $firstNode->left = $node;
                $previousNode = $node;
            }

            ++$column->size;
            $this->rowNodes[$rowId] = $firstNode;
        }
    }

    public function cover(ColumnNode $column): void
    {
        $column->right->left = $column->left;
        $column->left->right = $column->right;

        for ($row = $column->down; $row !== $column; $row = $row->down) {
            for ($node = $row->right; $node !== $row; $node = $node->right) {
                $node->down->up = $node->up;
                $node->up->down = $node->down;
                --$node->column->size;
            }
        }
    }

    public function uncover(ColumnNode $column): void
    {
        for ($row = $column->up; $row !== $column; $row = $row->up) {
            for ($node = $row->left; $node !== $row; $node = $node->left) {
                ++$node->column->size;
                $node->down->up = $node;
                $node->up->down = $node;
            }
        }

        $column->right->left = $column;
        $column->left->right = $column;
    }

    public function isSolved(): bool
    {
        return $this->header->right === $this->header;
    }

    public function chooseColumn(): ?ColumnNode
    {
        $best = null;

        for ($column = $this->header->right; $column !== $this->header; $column = $column->right) {
            if (!($column instanceof ColumnNode)) {
                continue;
            }

            if ($column->size === 0) {
                return null;
            }

            if ($best === null || $column->size < $best->size) {
                $best = $column;
            }
        }

        return $best;
    }

    public function header(): ColumnNode
    {
        return $this->header;
    }

    public function getColumn(int $index): ColumnNode
    {
        $column = $this->header->right;

        for ($i = 0; $i < $index; ++$i) {
            if (!($column instanceof ColumnNode) || $column === $this->header) {
                throw new \OutOfBoundsException(sprintf('Column index %d not found.', $index));
            }

            $column = $column->right;
        }

        if (!($column instanceof ColumnNode) || $column === $this->header) {
            throw new \OutOfBoundsException(sprintf('Column index %d not found.', $index));
        }

        return $column;
    }

    public function getRowNode(int $rowId): ?DancingNode
    {
        return $this->rowNodes[$rowId] ?? null;
    }
}
