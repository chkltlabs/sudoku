<?php

declare(strict_types=1);

namespace Sudoku\Tests\ExactCover;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\Board;
use Sudoku\ExactCover\DancingLinks\AlgorithmX;
use Sudoku\ExactCover\DancingLinks\DancingLinksMatrix;
use Sudoku\ExactCover\SudokuExactCoverProblem;

final class AlgorithmXTest extends TestCase
{
    public function testSudokuMatrixDimensions(): void
    {
        self::assertSame(324, SudokuExactCoverProblem::COLUMN_COUNT);
        self::assertSame(729, SudokuExactCoverProblem::ROW_COUNT);
    }

    public function testPossibilityEncodingRoundTrip(): void
    {
        $rowId = SudokuExactCoverProblem::possibilityIndex(4, 7, 9);
        $decoded = SudokuExactCoverProblem::decodePossibility($rowId);

        self::assertSame(4, $decoded['row']);
        self::assertSame(7, $decoded['col']);
        self::assertSame(9, $decoded['digit']);
    }

    public function testSolvesEmptyBoard(): void
    {
        $problem = new SudokuExactCoverProblem(new Board());
        $result = $problem->solve(1);

        self::assertCount(81, $result['rowIds']);
    }

    public function testClassicExactCoverExample(): void
    {
        $matrix = new DancingLinksMatrix(7);
        $matrix->addRow(0, [0, 3, 6]);
        $matrix->addRow(1, [0, 3]);
        $matrix->addRow(2, [3, 4, 6]);
        $matrix->addRow(3, [2, 4, 5]);
        $matrix->addRow(4, [1, 2, 5]);
        $matrix->addRow(5, [1, 6]);

        $algorithm = new AlgorithmX($matrix);
        $solution = $algorithm->solve(1);

        sort($solution);
        self::assertSame([1, 3, 5], $solution);
    }
}
