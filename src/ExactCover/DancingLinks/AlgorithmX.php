<?php

declare(strict_types=1);

namespace Sudoku\ExactCover\DancingLinks;

final class AlgorithmX
{
    private int $nodesVisited = 0;

    public function __construct(
        private readonly DancingLinksMatrix $matrix,
    ) {
    }

    /**
     * @return list<int>
     */
    public function solve(int $solutionLimit = 1): array
    {
        $this->nodesVisited = 0;
        $solution = [];
        $solutions = [];

        $this->search($solution, $solutions, $solutionLimit);

        return $solutions[0] ?? [];
    }

    public function countSolutions(int $limit = 2): int
    {
        $this->nodesVisited = 0;
        $count = 0;
        $solution = [];
        $this->countSearch($solution, $count, $limit);

        return $count;
    }

    public function nodesVisited(): int
    {
        return $this->nodesVisited;
    }

    /**
     * @param list<int> $solution
     * @param list<list<int>> $solutions
     */
    private function search(array &$solution, array &$solutions, int $solutionLimit): void
    {
        if (count($solutions) >= $solutionLimit) {
            return;
        }

        ++$this->nodesVisited;

        if ($this->matrix->isSolved()) {
            $solutions[] = $solution;

            return;
        }

        $column = $this->matrix->chooseColumn();

        if ($column === null) {
            return;
        }

        $this->matrix->cover($column);

        for ($row = $column->down; $row !== $column; $row = $row->down) {
            $solution[] = $row->rowId;

            for ($node = $row->right; $node !== $row; $node = $node->right) {
                $this->matrix->cover($node->column);
            }

            $this->search($solution, $solutions, $solutionLimit);

            array_pop($solution);

            for ($node = $row->left; $node !== $row; $node = $node->left) {
                $this->matrix->uncover($node->column);
            }
        }

        $this->matrix->uncover($column);
    }

    /**
     * @param list<int> $solution
     */
    private function countSearch(array &$solution, int &$count, int $limit): void
    {
        if ($count >= $limit) {
            return;
        }

        ++$this->nodesVisited;

        if ($this->matrix->isSolved()) {
            ++$count;

            return;
        }

        $column = $this->matrix->chooseColumn();

        if ($column === null) {
            return;
        }

        $this->matrix->cover($column);

        for ($row = $column->down; $row !== $column; $row = $row->down) {
            $solution[] = $row->rowId;

            for ($node = $row->right; $node !== $row; $node = $node->right) {
                $this->matrix->cover($node->column);
            }

            $this->countSearch($solution, $count, $limit);

            if ($count >= $limit) {
                array_pop($solution);

                for ($node = $row->left; $node !== $row; $node = $node->left) {
                    $this->matrix->uncover($node->column);
                }

                $this->matrix->uncover($column);

                return;
            }

            array_pop($solution);

            for ($node = $row->left; $node !== $row; $node = $node->left) {
                $this->matrix->uncover($node->column);
            }
        }

        $this->matrix->uncover($column);
    }
}
