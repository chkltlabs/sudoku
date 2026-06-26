<?php

declare(strict_types=1);

namespace Sudoku\Solver;

use Sudoku\Core\Board;

final readonly class SolverResult
{
    /**
     * @param list<string> $techniquesApplied
     */
    public function __construct(
        public bool $success,
        public ?Board $solution,
        public float $elapsedMs,
        public int $nodesVisited,
        public string $message = '',
        public bool $usedGuessing = false,
        public array $techniquesApplied = [],
    ) {
    }
}
