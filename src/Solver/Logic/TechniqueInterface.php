<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic;

use Sudoku\Core\CandidateGrid;

interface TechniqueInterface
{
    public function name(): string;

    public function apply(CandidateGrid $grid): bool;
}
