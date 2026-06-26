<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic\Techniques;

use Sudoku\Core\CandidateGrid;
use Sudoku\Solver\Logic\TechniqueInterface;

final class SimpleColoringTechnique implements TechniqueInterface
{
    public function name(): string
    {
        return 'simple-coloring';
    }

    public function apply(CandidateGrid $grid): bool
    {
        $progress = false;

        for ($digit = 1; $digit <= 9; ++$digit) {
            $bit = CandidateGrid::digitBit($digit);
            $graph = $this->buildStrongLinkGraph($grid, $digit);

            if ($graph === []) {
                continue;
            }

            $colors = [];
            $components = $this->colorComponents($graph, $colors);

            foreach ($components as $component) {
                $colorValues = [];

                foreach ($component as $node) {
                    $colorValues[$colors[$node]] = ($colorValues[$colors[$node]] ?? 0) + 1;
                }

                if (isset($colorValues[0], $colorValues[1]) && $colorValues[0] > 0 && $colorValues[1] > 0) {
                    foreach ($component as $node) {
                        [$row, $col] = $this->decodeNode($node);

                        if ($grid->getValue($row, $col) !== 0) {
                            continue;
                        }

                        if ($grid->eliminate($row, $col, $digit) === false) {
                            return false;
                        }

                        $progress = true;
                    }
                }
            }
        }

        return $progress;
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildStrongLinkGraph(CandidateGrid $grid, int $digit): array
    {
        $bit = CandidateGrid::digitBit($digit);
        $graph = [];

        for ($row = 0; $row < 9; ++$row) {
            $cols = [];

            for ($col = 0; $col < 9; ++$col) {
                if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                    $cols[] = $col;
                }
            }

            if (count($cols) === 2) {
                $this->addEdge($graph, $this->encodeNode($row, $cols[0]), $this->encodeNode($row, $cols[1]));
            }
        }

        for ($col = 0; $col < 9; ++$col) {
            $rows = [];

            for ($row = 0; $row < 9; ++$row) {
                if ($grid->getValue($row, $col) === 0 && ($grid->getCandidates($row, $col) & $bit) !== 0) {
                    $rows[] = $row;
                }
            }

            if (count($rows) === 2) {
                $this->addEdge($graph, $this->encodeNode($rows[0], $col), $this->encodeNode($rows[1], $col));
            }
        }

        return $graph;
    }

    /**
     * @param array<string, list<string>> $graph
     * @param array<string, int> $colors
     * @return list<list<string>>
     */
    private function colorComponents(array $graph, array &$colors): array
    {
        $components = [];

        foreach (array_keys($graph) as $node) {
            if (isset($colors[$node])) {
                continue;
            }

            $component = [];
            $queue = [$node];
            $colors[$node] = 0;
            $component[] = $node;

            while ($queue !== []) {
                $current = array_shift($queue);

                foreach ($graph[$current] ?? [] as $neighbor) {
                    if (!isset($colors[$neighbor])) {
                        $colors[$neighbor] = 1 - $colors[$current];
                        $component[] = $neighbor;
                        $queue[] = $neighbor;
                    } elseif ($colors[$neighbor] === $colors[$current]) {
                        $component['conflict'] = true;
                    }
                }
            }

            if (isset($component['conflict'])) {
                unset($component['conflict']);
                $components[] = $component;
            }
        }

        return $components;
    }

    /**
     * @param array<string, list<string>> $graph
     */
    private function addEdge(array &$graph, string $a, string $b): void
    {
        $graph[$a] ??= [];
        $graph[$b] ??= [];
        $graph[$a][] = $b;
        $graph[$b][] = $a;
    }

    private function encodeNode(int $row, int $col): string
    {
        return $row . ':' . $col;
    }

    /**
     * @return array{int, int}
     */
    private function decodeNode(string $node): array
    {
        [$row, $col] = explode(':', $node);

        return [(int) $row, (int) $col];
    }
}
