<?php

declare(strict_types=1);

namespace Sudoku\Tests\Solver;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\BoardParser;
use Sudoku\Core\SudokuValidator;
use Sudoku\Solver\ExactCoverSolver;

final class ExactCoverSolverTest extends TestCase
{
    public function testSolvesEasyFixture(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/easy.txt');
        $solver = new ExactCoverSolver();

        $result = $solver->solve($board);

        self::assertTrue($result->success);
        self::assertNotNull($result->solution);
        self::assertTrue($result->solution->isComplete());

        $validator = new SudokuValidator();
        self::assertTrue($validator->isValid($result->solution));

        for ($row = 0; $row < 9; ++$row) {
            for ($col = 0; $col < 9; ++$col) {
                $given = $board->get($row, $col);

                if ($given !== 0) {
                    self::assertSame($given, $result->solution->get($row, $col));
                }
            }
        }
    }

    public function testSolvesMediumFixture(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/medium.txt');
        $solver = new ExactCoverSolver();

        $result = $solver->solve($board);

        self::assertTrue($result->success);
        self::assertNotNull($result->solution);
        self::assertTrue($result->solution->isComplete());
    }

    public function testUnsolvablePuzzleReturnsFailure(): void
    {
        $parser = new BoardParser();
        $board = $parser->parse(<<<'PUZZLE'
        111000000
        000000000
        000000000
        000000000
        000000000
        000000000
        000000000
        000000000
        000000000
        PUZZLE);

        $solver = new ExactCoverSolver();
        $result = $solver->solve($board);

        self::assertFalse($result->success);
        self::assertNull($result->solution);
    }
}
