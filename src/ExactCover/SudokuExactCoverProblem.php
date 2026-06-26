<?php

declare(strict_types=1);

namespace Sudoku\ExactCover;

use Sudoku\Core\Board;
use Sudoku\ExactCover\DancingLinks\AlgorithmX;
use Sudoku\ExactCover\DancingLinks\DancingLinksMatrix;

final class SudokuExactCoverProblem
{
    public const int COLUMN_COUNT = 324;

    public const int ROW_COUNT = 729;

    public function __construct(
        private readonly Board $board,
    ) {
    }

    public function buildMatrix(): DancingLinksMatrix
    {
        $matrix = new DancingLinksMatrix(self::COLUMN_COUNT);

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                $given = $this->board->get($row, $col);

                for ($digit = 1; $digit <= Board::SIZE; ++$digit) {
                    if ($given !== 0 && $given !== $digit) {
                        continue;
                    }

                    $rowId = $this->possibilityIndex($row, $col, $digit);
                    $matrix->addRow($rowId, $this->columnsFor($row, $col, $digit));
                }
            }
        }

        return $matrix;
    }

    public function solve(int $limit = 1): array
    {
        $matrix = $this->buildMatrix();
        $algorithm = new AlgorithmX($matrix);

        return [
            'rowIds' => $algorithm->solve($limit),
            'nodesVisited' => $algorithm->nodesVisited(),
        ];
    }

    public function countSolutions(int $limit = 2): array
    {
        $matrix = $this->buildMatrix();
        $algorithm = new AlgorithmX($matrix);

        return [
            'count' => $algorithm->countSolutions($limit),
            'nodesVisited' => $algorithm->nodesVisited(),
        ];
    }

    public static function possibilityIndex(int $row, int $col, int $digit): int
    {
        return ($row * Board::SIZE + $col) * Board::SIZE + ($digit - 1);
    }

    /**
     * @return array{row: int, col: int, digit: int}
     */
    public static function decodePossibility(int $rowId): array
    {
        $digit = ($rowId % Board::SIZE) + 1;
        $cellIndex = intdiv($rowId, Board::SIZE);
        $row = intdiv($cellIndex, Board::SIZE);
        $col = $cellIndex % Board::SIZE;

        return ['row' => $row, 'col' => $col, 'digit' => $digit];
    }

    /**
     * @return list<int>
     */
    private function columnsFor(int $row, int $col, int $digit): array
    {
        $box = intdiv($row, 3) * 3 + intdiv($col, 3);

        return [
            $row * Board::SIZE + $col,
            81 + $row * Board::SIZE + ($digit - 1),
            162 + $col * Board::SIZE + ($digit - 1),
            243 + $box * Board::SIZE + ($digit - 1),
        ];
    }
}
