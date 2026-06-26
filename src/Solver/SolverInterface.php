<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Core\Board;

interface SolverInterface
{
    public function name(): string;

    public function solve(Board $board): SolverResult;
}
