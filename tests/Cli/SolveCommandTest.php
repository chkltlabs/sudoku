<?php

declare(strict_types=1);

namespace Sudoku\Tests\Cli;

use PHPUnit\Framework\TestCase;
use Sudoku\Cli\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SolveCommandTest extends TestCase
{
    public function testSolveWithGenerateAndSeed(): void
    {
        $tester = new CommandTester((new Application())->find('solve'));
        $exitCode = $tester->execute([
            '--generate' => 'medium',
            '--seed' => '99',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Generated puzzle: medium', $tester->getDisplay());
        self::assertStringContainsString('seed 99', $tester->getDisplay());
        self::assertStringContainsString('Solver:', $tester->getDisplay());
    }

    public function testSolveWithGenerateUsesRandomSeedWhenOmitted(): void
    {
        $tester = new CommandTester((new Application())->find('solve'));
        $exitCode = $tester->execute([
            '--generate' => 'easy',
        ]);

        self::assertSame(0, $exitCode);
        self::assertMatchesRegularExpression('/seed \d+/', $tester->getDisplay());
    }

    public function testSolveWithInvalidGenerateDifficultyFails(): void
    {
        $tester = new CommandTester((new Application())->find('solve'));
        $exitCode = $tester->execute([
            '--generate' => 'impossible',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid difficulty', $tester->getDisplay());
    }
}
