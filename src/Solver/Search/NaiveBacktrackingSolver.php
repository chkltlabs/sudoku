<?php

declare(strict_types=1);

namespace Sudoku\Solver\Search;

use Sudoku\Core\Board;
use Sudoku\Core\SudokuValidator;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class NaiveBacktrackingSolver extends AbstractSolver
{
    public function __construct(
        private readonly SudokuValidator $validator = new SudokuValidator(),
    ) {
    }

    public function name(): string
    {
        return 'naive-backtracking';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Search;
    }

    public function description(): string
    {
        return 'Row-major depth-first search with full constraint validation per placement.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;

        return $this->backtrack($board);
    }

    private function backtrack(Board $board): bool
    {
        ++$this->nodesVisited;

        if ($board->isComplete()) {
            return $this->validator->isValid($board);
        }

        for ($index = 0; $index < 81; ++$index) {
            $row = intdiv($index, 9);
            $col = $index % 9;

            if ($board->isEmpty($row, $col)) {
                for ($digit = 1; $digit <= 9; ++$digit) {
                    $board->set($row, $col, $digit);

                    if ($this->validator->isValid($board) && $this->backtrack($board)) {
                        return true;
                    }

                    $board->set($row, $col, 0);
                }

                return false;
            }
        }

        return false;
    }
}
