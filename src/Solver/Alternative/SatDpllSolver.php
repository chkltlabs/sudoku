<?php

declare(strict_types=1);

namespace Sudoku\Solver\Alternative;

use Sudoku\Core\Board;
use Sudoku\Solver\AbstractSolver;
use Sudoku\Solver\SolverCategory;

final class SatDpllSolver extends AbstractSolver
{
    /** @var list<list<int>> */
    private array $clauses = [];

    /** @var array<int, bool> */
    private array $assignment = [];

    public function name(): string
    {
        return 'sat-dpll';
    }

    public function category(): SolverCategory
    {
        return SolverCategory::Alternative;
    }

    public function description(): string
    {
        return 'Sudoku encoded as CNF and solved with the DPLL algorithm.';
    }

    protected function solveInternal(Board $board): bool
    {
        $this->usedGuessing = true;
        $this->clauses = $this->encode($board);
        $this->assignment = [];

        if (!$this->dpll()) {
            return false;
        }

        for ($row = 0; $row < Board::SIZE; ++$row) {
            for ($col = 0; $col < Board::SIZE; ++$col) {
                $value = $board->get($row, $col);

                if ($value !== 0) {
                    continue;
                }

                for ($digit = 1; $digit <= Board::SIZE; ++$digit) {
                    if ($this->assignment[$this->var($row, $col, $digit)] ?? false) {
                        $board->set($row, $col, $digit);
                        break;
                    }
                }
            }
        }

        return true;
    }

    private function var(int $row, int $col, int $digit): int
    {
        return ($row * 81) + ($col * 9) + $digit;
    }

    /**
     * @return list<list<int>>
     */
    private function encode(Board $board): array
    {
        $clauses = [];

        for ($row = 0; $row < 9; ++$row) {
            for ($col = 0; $col < 9; ++$col) {
                $cellClause = [];

                for ($digit = 1; $digit <= 9; ++$digit) {
                    $cellClause[] = $this->var($row, $col, $digit);
                }

                $clauses[] = $cellClause;

                for ($d1 = 1; $d1 <= 9; ++$d1) {
                    for ($d2 = $d1 + 1; $d2 <= 9; ++$d2) {
                        $clauses[] = [
                            -$this->var($row, $col, $d1),
                            -$this->var($row, $col, $d2),
                        ];
                    }
                }
            }
        }

        for ($row = 0; $row < 9; ++$row) {
            for ($digit = 1; $digit <= 9; ++$digit) {
                $atLeastOne = [];

                for ($col = 0; $col < 9; ++$col) {
                    $atLeastOne[] = $this->var($row, $col, $digit);
                }

                $clauses[] = $atLeastOne;

                for ($c1 = 0; $c1 < 9; ++$c1) {
                    for ($c2 = $c1 + 1; $c2 < 9; ++$c2) {
                        $clauses[] = [
                            -$this->var($row, $c1, $digit),
                            -$this->var($row, $c2, $digit),
                        ];
                    }
                }
            }
        }

        for ($col = 0; $col < 9; ++$col) {
            for ($digit = 1; $digit <= 9; ++$digit) {
                $atLeastOne = [];

                for ($row = 0; $row < 9; ++$row) {
                    $atLeastOne[] = $this->var($row, $col, $digit);
                }

                $clauses[] = $atLeastOne;

                for ($r1 = 0; $r1 < 9; ++$r1) {
                    for ($r2 = $r1 + 1; $r2 < 9; ++$r2) {
                        $clauses[] = [
                            -$this->var($r1, $col, $digit),
                            -$this->var($r2, $col, $digit),
                        ];
                    }
                }
            }
        }

        for ($box = 0; $box < 9; ++$box) {
            for ($digit = 1; $digit <= 9; ++$digit) {
                $atLeastOne = [];
                $cells = $this->boxCells($box);

                foreach ($cells as [$row, $col]) {
                    $atLeastOne[] = $this->var($row, $col, $digit);
                }

                $clauses[] = $atLeastOne;

                for ($i = 0; $i < count($cells); ++$i) {
                    for ($j = $i + 1; $j < count($cells); ++$j) {
                        [$r1, $c1] = $cells[$i];
                        [$r2, $c2] = $cells[$j];
                        $clauses[] = [
                            -$this->var($r1, $c1, $digit),
                            -$this->var($r2, $c2, $digit),
                        ];
                    }
                }
            }
        }

        for ($row = 0; $row < 9; ++$row) {
            for ($col = 0; $col < 9; ++$col) {
                $value = $board->get($row, $col);

                if ($value === 0) {
                    continue;
                }

                for ($digit = 1; $digit <= 9; ++$digit) {
                    if ($digit === $value) {
                        $clauses[] = [$this->var($row, $col, $digit)];
                    } else {
                        $clauses[] = [-$this->var($row, $col, $digit)];
                    }
                }
            }
        }

        return $clauses;
    }

    /**
     * @return list<array{int, int}>
     */
    private function boxCells(int $box): array
    {
        $cells = [];
        $startRow = intdiv($box, 3) * 3;
        $startCol = ($box % 3) * 3;

        for ($row = $startRow; $row < $startRow + 3; ++$row) {
            for ($col = $startCol; $col < $startCol + 3; ++$col) {
                $cells[] = [$row, $col];
            }
        }

        return $cells;
    }

    private function dpll(): bool
    {
        ++$this->nodesVisited;

        if (!$this->unitPropagate()) {
            return false;
        }

        if ($this->allClausesSatisfied()) {
            return true;
        }

        $literal = $this->chooseLiteral();

        if ($literal === null) {
            return true;
        }

        foreach ([$literal, -$literal] as $choice) {
            $savedClauses = $this->clauses;
            $savedAssignment = $this->assignment;

            $this->assignment[abs($choice)] = $choice > 0;
            $this->clauses[] = [$choice];

            if ($this->dpll()) {
                return true;
            }

            $this->clauses = $savedClauses;
            $this->assignment = $savedAssignment;
        }

        return false;
    }

    private function unitPropagate(): bool
    {
        $changed = true;

        while ($changed) {
            $changed = false;

            foreach ($this->clauses as $clause) {
                if ($clause === []) {
                    return false;
                }

                $unassigned = [];
                $satisfied = false;

                foreach ($clause as $literal) {
                    $var = abs($literal);

                    if (($this->assignment[$var] ?? null) === ($literal > 0)) {
                        $satisfied = true;
                        break;
                    }

                    if (!isset($this->assignment[$var])) {
                        $unassigned[] = $literal;
                    }
                }

                if ($satisfied) {
                    continue;
                }

                if (count($unassigned) === 1) {
                    $literal = $unassigned[0];
                    $this->assignment[abs($literal)] = $literal > 0;
                    $changed = true;
                }
            }
        }

        return true;
    }

    private function allClausesSatisfied(): bool
    {
        foreach ($this->clauses as $clause) {
            $satisfied = false;

            foreach ($clause as $literal) {
                $var = abs($literal);

                if (($this->assignment[$var] ?? null) === ($literal > 0)) {
                    $satisfied = true;
                    break;
                }
            }

            if (!$satisfied) {
                return false;
            }
        }

        return true;
    }

    private function chooseLiteral(): ?int
    {
        foreach ($this->clauses as $clause) {
            foreach ($clause as $literal) {
                if (!isset($this->assignment[abs($literal)])) {
                    return $literal;
                }
            }
        }

        return null;
    }
}
