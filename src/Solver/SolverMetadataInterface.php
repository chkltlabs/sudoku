<?php

declare(strict_types=1);

namespace Sudoku\Solver;

interface SolverMetadataInterface
{
    public function category(): SolverCategory;

    public function description(): string;
}
