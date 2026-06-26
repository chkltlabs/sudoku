<?php

declare(strict_types=1);

namespace Sudoku\Solver;

enum SolverCategory: string
{
    case ExactCover = 'exact-cover';
    case Search = 'search';
    case Propagation = 'propagation';
    case HumanLogic = 'human-logic';
    case Hybrid = 'hybrid';
    case Optimization = 'optimization';
    case Alternative = 'alternative';
}
