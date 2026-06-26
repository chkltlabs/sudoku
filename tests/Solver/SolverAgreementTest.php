<?php

declare(strict_types=1);

namespace Sudoku\Tests\Solver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sudoku\Core\BoardParser;
use Sudoku\Solver\ExactCoverSolver;
use Sudoku\Solver\Search\BitmaskMrvSolver;
use Sudoku\Solver\Search\ForwardCheckingSolver;
use Sudoku\Solver\Search\MrvLcvSolver;
use Sudoku\Solver\Search\NaiveBacktrackingSolver;
use Sudoku\Solver\Search\NorvigFullSolver;
use Sudoku\Solver\Search\NorvigSolver;
use Sudoku\Solver\SolverInterface;

final class SolverAgreementTest extends TestCase
{
    /**
     * @return list<array{string}>
     */
    public static function fixtureProvider(): array
    {
        return [
            ['easy.txt'],
            ['medium.txt'],
        ];
    }

    /**
     * @return list<SolverInterface>
     */
    private function deterministicSolvers(): array
    {
        return [
            new ExactCoverSolver(),
            new BitmaskMrvSolver(),
            new ForwardCheckingSolver(),
            new MrvLcvSolver(),
            new NorvigSolver(),
            new NorvigFullSolver(),
        ];
    }

    #[DataProvider('fixtureProvider')]
    public function testDeterministicSolversAgree(string $fixture): void
    {
        $parser = new BoardParser();
        $board = $parser->parseFile(__DIR__ . '/../fixtures/' . $fixture);
        $reference = (new ExactCoverSolver())->solve($board);

        self::assertTrue($reference->success);
        self::assertNotNull($reference->solution);

        foreach ($this->deterministicSolvers() as $solver) {
            $result = $solver->solve($board->clone());

            self::assertTrue(
                $result->success,
                sprintf('%s failed to solve %s', $solver->name(), $fixture),
            );
            self::assertNotNull($result->solution);
            self::assertTrue(
                $reference->solution->equals($result->solution),
                sprintf('%s produced a different solution for %s', $solver->name(), $fixture),
            );
        }
    }
}
