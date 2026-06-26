<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic;

use Sudoku\Solver\Logic\Techniques\HiddenPairTechnique;
use Sudoku\Solver\Logic\Techniques\HiddenSingleTechnique;
use Sudoku\Solver\Logic\Techniques\NakedPairTechnique;
use Sudoku\Solver\Logic\Techniques\NakedSingleTechnique;
use Sudoku\Solver\Logic\Techniques\PointingPairTechnique;

final class HumanBasicSolver extends PropagationSolver
{
    public function __construct()
    {
        parent::__construct([
            new NakedSingleTechnique(),
            new HiddenSingleTechnique(),
            new PointingPairTechnique(),
            new NakedPairTechnique(),
            new HiddenPairTechnique(),
        ]);
    }

    public function name(): string
    {
        return 'human-basic';
    }

    public function description(): string
    {
        return 'Logic-only solver using singles, pointing pairs, and naked/hidden pairs.';
    }
}
