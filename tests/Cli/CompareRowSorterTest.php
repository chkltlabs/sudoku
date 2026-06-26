<?php

declare(strict_types=1);

namespace Sudoku\Tests\Cli;

use PHPUnit\Framework\TestCase;
use Sudoku\Cli\Command\CompareResultRow;
use Sudoku\Cli\Command\CompareRowSorter;

final class CompareRowSorterTest extends TestCase
{
    public function testDefaultSortIsBySolverName(): void
    {
        $rows = [
            $this->row('zebra', 10.0),
            $this->row('alpha', 5.0),
            $this->row('mango', 1.0),
        ];

        $sorted = (new CompareRowSorter())->sort($rows, null);

        self::assertSame(['alpha', 'mango', 'zebra'], array_map(static fn (CompareResultRow $row): string => $row->solver, $sorted));
    }

    public function testSortsByTimeThenSolver(): void
    {
        $rows = [
            $this->row('slow-b', 20.0),
            $this->row('fast-b', 20.0),
            $this->row('fast-a', 1.0),
        ];

        $sorted = (new CompareRowSorter())->sort($rows, 'time,solver');

        self::assertSame(['fast-a', 'fast-b', 'slow-b'], array_map(static fn (CompareResultRow $row): string => $row->solver, $sorted));
    }

    public function testRejectsInvalidSortKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new CompareRowSorter())->parseSortKeys('solver,invalid');
    }

    private function row(string $solver, float $timeMs): CompareResultRow
    {
        return new CompareResultRow(
            solver: $solver,
            category: 'search',
            successDisplay: 'yes',
            successRate: 1.0,
            timeMs: $timeMs,
            nodesVisited: 1,
            guessingDisplay: 'yes',
            guessingSort: 1,
        );
    }
}
