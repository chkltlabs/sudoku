<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Core\Board;

interface SolutionCounterInterface
{
    /**
     * @return array{count: int, nodesVisited: int, elapsedMs: float}
     */
    public function countSolutions(Board $board, int $limit = 2): array;
}
