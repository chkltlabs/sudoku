<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

final class CompareRowSorter
{
    private const string DEFAULT_SORT = 'solver';

    /** @var array<string, string> */
    private const array SORT_KEY_ALIASES = [
        'solver' => 'solver',
        'name' => 'solver',
        'category' => 'category',
        'success' => 'successRate',
        'time' => 'timeMs',
        'elapsed' => 'timeMs',
        'ms' => 'timeMs',
        'time_ms' => 'timeMs',
        'nodes' => 'nodesVisited',
        'nodes_visited' => 'nodesVisited',
        'guessing' => 'guessingSort',
        'guessed' => 'guessingSort',
    ];

    /**
     * @param list<CompareResultRow> $rows
     * @return list<CompareResultRow>
     */
    public function sort(array $rows, ?string $sortOption): array
    {
        $keys = $this->parseSortKeys($sortOption);

        usort($rows, function (CompareResultRow $left, CompareResultRow $right) use ($keys): int {
            foreach ($keys as $key) {
                $comparison = $this->compare($left, $right, $key);

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        });

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function parseSortKeys(?string $sortOption): array
    {
        $raw = $sortOption === null || $sortOption === ''
            ? self::DEFAULT_SORT
            : $sortOption;

        $keys = [];

        foreach (explode(',', $raw) as $part) {
            $normalized = strtolower(trim($part));

            if ($normalized === '') {
                continue;
            }

            if (!isset(self::SORT_KEY_ALIASES[$normalized])) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid sort key "%s". Use solver, category, success, time, nodes, or guessing.',
                    $part,
                ));
            }

            $keys[] = self::SORT_KEY_ALIASES[$normalized];
        }

        if ($keys === []) {
            $keys[] = self::SORT_KEY_ALIASES[self::DEFAULT_SORT];
        }

        return $keys;
    }

    private function compare(CompareResultRow $left, CompareResultRow $right, string $key): int
    {
        return match ($key) {
            'solver' => $left->solver <=> $right->solver,
            'category' => $left->category <=> $right->category,
            'successRate' => $left->successRate <=> $right->successRate,
            'timeMs' => $left->timeMs <=> $right->timeMs,
            'nodesVisited' => $left->nodesVisited <=> $right->nodesVisited,
            'guessingSort' => $left->guessingSort <=> $right->guessingSort,
            default => throw new \InvalidArgumentException(sprintf('Unsupported sort field: %s', $key)),
        };
    }
}
