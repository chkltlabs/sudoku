<?php

declare(strict_types=1);

namespace Sudoku\Tests\Cli;

use PHPUnit\Framework\TestCase;
use Sudoku\Cli\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CompareCommandTest extends TestCase
{
    public function testCompareSortsByTime(): void
    {
        $tester = new CommandTester((new Application())->find('compare'));
        $exitCode = $tester->execute([
            '--file' => __DIR__ . '/../fixtures/medium.txt',
            '--category' => 'search',
            '--sort' => 'time',
        ]);

        self::assertSame(0, $exitCode);

        $display = $tester->getDisplay();
        $norvigPos = strpos($display, 'norvig');
        $naivePos = strpos($display, 'naive-backtracking');

        self::assertNotFalse($norvigPos);
        self::assertNotFalse($naivePos);
        self::assertLessThan($naivePos, $norvigPos);
    }
}
