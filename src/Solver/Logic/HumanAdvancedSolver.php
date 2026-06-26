<?php

declare(strict_types=1);

namespace Sudoku\Solver\Logic;

use Sudoku\Solver\Logic\Techniques\HiddenPairTechnique;
use Sudoku\Solver\Logic\Techniques\HiddenSingleTechnique;
use Sudoku\Solver\Logic\Techniques\NakedPairTechnique;
use Sudoku\Solver\Logic\Techniques\NakedSingleTechnique;
use Sudoku\Solver\Logic\Techniques\PointingPairTechnique;
use Sudoku\Solver\Logic\Techniques\SimpleColoringTechnique;
use Sudoku\Solver\Logic\Techniques\SwordfishTechnique;
use Sudoku\Solver\Logic\Techniques\XWingTechnique;
use Sudoku\Solver\Logic\Techniques\XyWingTechnique;

final class HumanAdvancedSolver extends PropagationSolver
{
    public function __construct()
    {
        parent::__construct([
            new NakedSingleTechnique(),
            new HiddenSingleTechnique(),
            new PointingPairTechnique(),
            new NakedPairTechnique(),
            new HiddenPairTechnique(),
            new XWingTechnique(),
            new SwordfishTechnique(),
            new XyWingTechnique(),
            new SimpleColoringTechnique(),
        ]);
    }

    public function name(): string
    {
        return 'human-advanced';
    }

    public function description(): string
    {
        return 'Logic-only solver with basic techniques plus fish, wings, and coloring.';
    }
}
