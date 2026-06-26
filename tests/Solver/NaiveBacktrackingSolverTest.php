<?php

declare(strict_types=1);

namespace Sudoku\Tests\Solver;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\BoardParser;
use Sudoku\Solver\ExactCoverSolver;
use Sudoku\Solver\Search\NaiveBacktrackingSolver;

final class NaiveBacktrackingSolverTest extends TestCase
{
    public function testSolvesEasyFixture(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/easy.txt');
        $reference = (new ExactCoverSolver())->solve($board);
        $result = (new NaiveBacktrackingSolver())->solve($board);

        self::assertTrue($result->success);
        self::assertNotNull($result->solution);
        self::assertNotNull($reference->solution);
        self::assertTrue($reference->solution->equals($result->solution));
    }
}
