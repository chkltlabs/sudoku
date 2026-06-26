<?php

declare(strict_types=1);

namespace Sudoku\Tests\Generator;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\SudokuValidator;
use Sudoku\Generator\Difficulty;
use Sudoku\Generator\PuzzleGenerator;
use Sudoku\Solver\ExactCoverSolver;

final class PuzzleGeneratorTest extends TestCase
{
    public function testGeneratedPuzzleIsValidAndUnique(): void
    {
        $generator = new PuzzleGenerator();
        $solver = new ExactCoverSolver();
        $validator = new SudokuValidator();

        foreach (Difficulty::cases() as $difficulty) {
            $generated = $generator->generate($difficulty, 12345);

            self::assertSame($difficulty, $generated->difficulty);
            self::assertTrue($validator->isValid($generated->board));
            self::assertGreaterThanOrEqual($difficulty->minimumClues(), $generated->clueCount);
            self::assertLessThanOrEqual(81, $generated->clueCount);

            $count = $solver->countSolutions($generated->board, 2);
            self::assertSame(1, $count['count']);
        }
    }

    public function testSeedProducesReproduciblePuzzle(): void
    {
        $generator = new PuzzleGenerator();

        $first = $generator->generate(Difficulty::Medium, 99);
        $second = $generator->generate(Difficulty::Medium, 99);

        self::assertTrue($first->board->equals($second->board));
    }
}
