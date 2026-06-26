<?php

declare(strict_types=1);

namespace Sudoku\Tests\Core;

use PHPUnit\Framework\TestCase;
use Sudoku\Core\Board;
use Sudoku\Core\BoardFormatter;
use Sudoku\Core\BoardParser;

final class BoardTest extends TestCase
{
    public function testParseNineLinesAndFormat(): void
    {
        $input = <<<'PUZZLE'
        530070000
        901000000
        000000000
        000000000
        000000000
        000000000
        000000000
        000000000
        000000000
        PUZZLE;

        $parser = new BoardParser();
        $board = $parser->parse($input);

        self::assertSame(5, $board->get(0, 0));
        self::assertSame(3, $board->get(0, 1));
        self::assertFalse($board->isComplete());
        self::assertSame(5, $board->clueCount());

        $formatter = new BoardFormatter();
        $formatted = $formatter->format($board);

        self::assertStringContainsString('| 5 3 . |', $formatted);
    }

    public function testParseFlatString(): void
    {
        $parser = new BoardParser();
        $board = $parser->parse(str_repeat('.', 81));

        self::assertTrue($board->isComplete() === false);
        self::assertSame(0, $board->clueCount());
        self::assertSame(str_repeat('.', 81), $board->toFlatString());
    }

    public function testCloneIsIndependent(): void
    {
        $board = new Board();
        $board->set(0, 0, 7);
        $clone = $board->clone();
        $clone->set(0, 0, 1);

        self::assertSame(7, $board->get(0, 0));
        self::assertSame(1, $clone->get(0, 0));
    }
}
