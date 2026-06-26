<?php

declare(strict_types=1);

namespace Sudoku\Generator;

use Sudoku\Core\Board;
use Sudoku\Core\Coordinate;
use Sudoku\Solver\ExactCoverSolver;

final readonly class GeneratedPuzzle
{
    public function __construct(
        public Board $board,
        public Difficulty $difficulty,
        public int $clueCount,
    ) {
    }
}

final class PuzzleGenerator
{
    public function __construct(
        private readonly ExactCoverSolver $solver = new ExactCoverSolver(),
    ) {
    }

    public function generate(Difficulty $difficulty, ?int $seed = null): GeneratedPuzzle
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        $complete = $this->createCompleteBoard();
        $puzzle = $this->removeClues($complete, $difficulty);

        return new GeneratedPuzzle(
            board: $puzzle,
            difficulty: $difficulty,
            clueCount: $puzzle->clueCount(),
        );
    }

    private function createCompleteBoard(): Board
    {
        $result = $this->solver->solve(new Board());

        if (!$result->success || $result->solution === null) {
            throw new \RuntimeException('Failed to generate a complete Sudoku grid.');
        }

        return $this->randomize($result->solution);
    }

    private function randomize(Board $board): Board
    {
        $digitMap = range(1, 9);
        shuffle($digitMap);
        array_unshift($digitMap, 0);

        $result = new Board();

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                $value = $board->get($row, $col);
                $result->set($row, $col, $digitMap[$value]);
            }
        }

        $result = $this->permuteRowsWithinBands($result);
        $result = $this->permuteColumnsWithinStacks($result);
        $result = $this->permuteBands($result);

        return $this->permuteStacks($result);
    }

    private function permuteRowsWithinBands(Board $board): Board
    {
        $result = new Board();

        for ($band = 0; $band < 3; ++$band) {
            $rows = [$band * 3, $band * 3 + 1, $band * 3 + 2];
            shuffle($rows);

            for ($offset = 0; $offset < 3; ++$offset) {
                $sourceRow = $rows[$offset];
                $targetRow = $band * 3 + $offset;

                for ($col = 0; $col < Board::SIZE; ++$col) {
                    $result->set($targetRow, $col, $board->get($sourceRow, $col));
                }
            }
        }

        return $result;
    }

    private function permuteColumnsWithinStacks(Board $board): Board
    {
        $result = new Board();

        for ($stack = 0; $stack < 3; ++$stack) {
            $columns = [$stack * 3, $stack * 3 + 1, $stack * 3 + 2];
            shuffle($columns);

            for ($offset = 0; $offset < 3; ++$offset) {
                $sourceCol = $columns[$offset];
                $targetCol = $stack * 3 + $offset;

                for ($row = 0; $row < Board::SIZE; ++$row) {
                    $result->set($row, $targetCol, $board->get($row, $sourceCol));
                }
            }
        }

        return $result;
    }

    private function permuteBands(Board $board): Board
    {
        $bands = [0, 1, 2];
        shuffle($bands);
        $result = new Board();

        for ($targetBand = 0; $targetBand < 3; ++$targetBand) {
            $sourceBand = $bands[$targetBand];

            for ($offset = 0; $offset < 3; ++$offset) {
                $sourceRow = $sourceBand * 3 + $offset;
                $targetRow = $targetBand * 3 + $offset;

                for ($col = 0; $col < Board::SIZE; ++$col) {
                    $result->set($targetRow, $col, $board->get($sourceRow, $col));
                }
            }
        }

        return $result;
    }

    private function permuteStacks(Board $board): Board
    {
        $stacks = [0, 1, 2];
        shuffle($stacks);
        $result = new Board();

        for ($targetStack = 0; $targetStack < 3; ++$targetStack) {
            $sourceStack = $stacks[$targetStack];

            for ($offset = 0; $offset < 3; ++$offset) {
                $sourceCol = $sourceStack * 3 + $offset;
                $targetCol = $targetStack * 3 + $offset;

                for ($row = 0; $row < Board::SIZE; ++$row) {
                    $result->set($row, $targetCol, $board->get($row, $sourceCol));
                }
            }
        }

        return $result;
    }

    private function removeClues(Board $complete, Difficulty $difficulty): Board
    {
        $puzzle = $complete->clone();
        $coordinates = [];

        for ($index = 0; $index < 81; ++$index) {
            $coordinates[] = Coordinate::fromIndex($index);
        }

        shuffle($coordinates);

        $target = $difficulty->targetClues();
        $minimum = $difficulty->minimumClues();

        foreach ($coordinates as $coordinate) {
            if ($puzzle->clueCount() <= $target) {
                break;
            }

            if ($puzzle->clueCount() <= $minimum) {
                break;
            }

            $row = $coordinate->row;
            $col = $coordinate->col;
            $saved = $puzzle->get($row, $col);

            if ($saved === 0) {
                continue;
            }

            $puzzle->set($row, $col, 0);

            $countResult = $this->solver->countSolutions($puzzle, 2);

            if ($countResult['count'] !== 1) {
                $puzzle->set($row, $col, $saved);
            }
        }

        return $puzzle;
    }
}
