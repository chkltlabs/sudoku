# Sudoku Generator & Solver

PHP CLI tool for generating and solving 9×9 Sudoku puzzles using the [Exact Cover](https://en.wikipedia.org/wiki/Exact_cover#Sudoku) formulation solved with Algorithm X and Dancing Links (DLX).

## Requirements

- PHP 8.3+

## Installation

```bash
cd ~/Code/sudoku
composer install
chmod +x bin/sudoku
```

## Usage

### Generate a puzzle

```bash
./bin/sudoku generate --difficulty=easy
./bin/sudoku generate --difficulty=medium --seed=42
./bin/sudoku generate --difficulty=hard
```

### Solve a puzzle

```bash
./bin/sudoku solve --file puzzle.txt
./bin/sudoku solve --solver=exact-cover < puzzle.txt
./bin/sudoku solve --generate=medium
./bin/sudoku solve --generate=hard --seed=42
```

### Validate a puzzle

```bash
./bin/sudoku validate --file puzzle.txt
```

### Compare solvers

```bash
./bin/sudoku compare --file puzzle.txt
./bin/sudoku compare --category=search --file puzzle.txt
./bin/sudoku compare --sort=time,solver --category=search --file puzzle.txt
./bin/sudoku compare --category=optimization --runs=5 --seed=42 --file puzzle.txt
```

### Benchmark fixtures to CSV

```bash
./bin/sudoku bench --fixtures tests/fixtures --output results.csv
./bin/sudoku bench --category=human-logic --fixtures tests/fixtures
```

## Registered solvers

| Name | Category | Description |
|------|----------|-------------|
| `exact-cover` | exact-cover | Algorithm X + Dancing Links |
| `naive-backtracking` | search | Row-major DFS with validation |
| `bitmask-mrv` | search | Bitmask candidates + MRV |
| `forward-checking` | search | DFS with candidate forward checking |
| `mrv-lcv` | search | MRV + least-constraining value |
| `norvig` | search | Arc consistency + MRV search |
| `norvig-full` | hybrid | AC + naked pairs + MRV |
| `human-basic` | human-logic | Singles, pointing pairs, pairs |
| `human-advanced` | human-logic | Basic + fish, wings, coloring |
| `propagate-then-search` | hybrid | Human-basic then bitmask search |
| `sat-dpll` | alternative | CNF encoding + DPLL |
| `pattern-overlay` | alternative | Row permutation overlay |
| `simulated-annealing` | optimization | Stochastic constraint minimization |
| `genetic-algorithm` | optimization | Population-based search |

## Input format

Puzzles can be provided as:

- A file with 9 lines of 9 characters (`1-9`, `.` or `0` for empty cells)
- A single 81-character string
- Stdin pipe

## Difficulty tiers

| Tier   | Target clues | Minimum clues |
|--------|-------------|---------------|
| Easy   | 40          | 36            |
| Medium | 34          | 30            |
| Hard   | 28          | 24            |

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

## Architecture

Solvers implement `Sudoku\Solver\SolverInterface`, allowing additional algorithms to be registered for benchmarking via the `compare` command.
