<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Core\Board;
use Sudoku\ExactCover\SudokuExactCoverProblem;

final class ExactCoverSolver implements SolverInterface, SolverMetadataInterface, SolutionCounterInterface
{
    public function name(): string
    {
        return 'exact-cover';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::ExactCover;
    }

    public function description(): string
    {
        return 'Algorithm X with Dancing Links on the 729x324 Sudoku exact cover matrix.';
    }

    public function solve(Board $board): SolverResult
    {
        $start = hrtime(true);
        $problem = new SudokuExactCoverProblem($board);
        $result = $problem->solve(1);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $rowIds = $result['rowIds'];

        if ($rowIds === []) {
            return new SolverResult(
                success: false,
                solution: null,
                elapsedMs: $elapsedMs,
                nodesVisited: $result['nodesVisited'],
                message: 'No solution found.',
                usedGuessing: true,
            );
        }

        $solution = $board->clone();

        foreach ($rowIds as $rowId) {
            $decoded = SudokuExactCoverProblem::decodePossibility($rowId);
            $solution->set($decoded['row'], $decoded['col'], $decoded['digit']);
        }

        return new SolverResult(
            success: true,
            solution: $solution,
            elapsedMs: $elapsedMs,
            nodesVisited: $result['nodesVisited'],
            message: 'Solved with Exact Cover (DLX).',
            usedGuessing: true,
        );
    }

    public function countSolutions(Board $board, int $limit = 2): array
    {
        $start = hrtime(true);
        $problem = new SudokuExactCoverProblem($board);
        $result = $problem->countSolutions($limit);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        return [
            'count' => $result['count'],
            'nodesVisited' => $result['nodesVisited'],
            'elapsedMs' => $elapsedMs,
        ];
    }
}
