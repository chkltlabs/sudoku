<?php

declare(strict_types=1);

namespace Sudoku\Tests\Solver;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\BoardParser;
use Sudoku\Solver\Hybrid\PropagateThenSearchSolver;
use Sudoku\Solver\Logic\HumanAdvancedSolver;
use Sudoku\Solver\Logic\HumanBasicSolver;
use Sudoku\Solver\SolverRegistry;

final class SolverRegistryTest extends TestCase
{
    public function testRegistryContainsExpectedSolvers(): void
    {
        $registry = new SolverRegistry();
        $names = $registry->names();

        self::assertContains('exact-cover', $names);
        self::assertContains('norvig', $names);
        self::assertContains('human-basic', $names);
        self::assertContains('propagate-then-search', $names);
        self::assertGreaterThanOrEqual(14, count($names));
    }

    public function testHumanBasicMayNotSolveHardGeneratedPuzzle(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/medium.txt');
        $solver = new HumanBasicSolver();
        $result = $solver->solve($board);

        self::assertFalse($result->usedGuessing);
    }

    public function testPropagateThenSearchSolvesMediumFixture(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/medium.txt');
        $solver = new PropagateThenSearchSolver();
        $result = $solver->solve($board);

        self::assertTrue($result->success);
        self::assertNotEmpty($result->techniquesApplied);
    }

    public function testHumanAdvancedAttemptsAdditionalTechniques(): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/medium.txt');
        $solver = new HumanAdvancedSolver();
        $result = $solver->solve($board);

        self::assertFalse($result->usedGuessing);
    }
}
