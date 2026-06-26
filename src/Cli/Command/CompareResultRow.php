<?php

declare(strict_types=1);

namespace Sudoku\Cli\Command;

final readonly class CompareResultRow
{
    public function __construct(
        public string $solver,
        public string $category,
        public string $successDisplay,
        public float $successRate,
        public float $timeMs,
        public int $nodesVisited,
        public string $guessingDisplay,
        public int $guessingSort,
    ) {
    }

    /**
     * @return list<string>
     */
    public function toTableRow(): array
    {
        return [
            $this->solver,
            $this->category,
            $this->successDisplay,
            sprintf('%.2f', $this->timeMs),
            (string) $this->nodesVisited,
            $this->guessingDisplay,
        ];
    }
}
