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

## Commands

| Command | Description |
|---------|-------------|
| `generate` | Create a new puzzle |
| `solve` | Solve a puzzle |
| `validate` | Check layout validity and solution uniqueness |
| `compare` | Benchmark all registered solvers on one puzzle |
| `bench` | Benchmark solvers across fixture files (CSV output) |

Global options (all commands): `-h`, `--help`, `-q`, `--quiet`, `-V`, `--version`, `-n`, `--no-interaction`, `-v` / `-vv` / `-vvv`

---

### `generate`

Generate a new Sudoku puzzle.

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--difficulty` | `-d` | `medium` | Difficulty tier: `easy`, `medium`, or `hard` |
| `--seed` | `-s` | *(none)* | Integer seed for reproducible puzzles |

```bash
./bin/sudoku generate
./bin/sudoku generate --difficulty=easy
./bin/sudoku generate -d hard -s 42
```

---

### `solve`

Solve a puzzle from a file, stdin, or a freshly generated puzzle.

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--solver` | | `exact-cover` | Solver name (see [Registered solvers](#registered-solvers)) |
| `--file` | `-f` | *(none)* | Path to a puzzle file |
| `--generate` | `-g` | *(none)* | Generate a puzzle to solve: `easy`, `medium`, or `hard` |
| `--seed` | `-s` | *(random)* | Seed when using `--generate`; random integer if omitted |

**Input priority:** `--generate` → `--file` → stdin pipe.

```bash
./bin/sudoku solve --file puzzle.txt
./bin/sudoku solve --solver=norvig < puzzle.txt
./bin/sudoku solve --generate=medium
./bin/sudoku solve -g hard --seed=42 --solver=bitmask-mrv
```

---

### `validate`

Validate a puzzle's layout and report whether it has zero, one, or multiple solutions.

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--file` | `-f` | *(none)* | Path to a puzzle file |

**Input:** `--file` or stdin pipe.

**Output:** valid layout, complete/incomplete, clue count, solution status (`unique solution`, `multiple solutions`, or `unsolvable`).

```bash
./bin/sudoku validate --file puzzle.txt
cat puzzle.txt | ./bin/sudoku validate
```

---

### `compare`

Run every registered solver (optionally filtered) on a single puzzle and print a comparison table.

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--file` | `-f` | *(none)* | Path to a puzzle file |
| `--category` | `-c` | *(all)* | Filter solvers by category (see below) |
| `--runs` | `-r` | `1` | Number of runs per solver (for stochastic solvers) |
| `--seed` | `-s` | *(none)* | Base seed for stochastic solvers (`seed + run` per iteration) |
| `--sort` | | `solver` | Comma-separated sort keys (see below) |

**Input:** `--file` or stdin pipe.

**Output columns:** Solver, Category, Success, Time (ms), Nodes visited, Guessing.

**Categories** (`--category`): `exact-cover`, `search`, `propagation`, `human-logic`, `hybrid`, `optimization`, `alternative`.

**Sort keys** (`--sort`): comma-separated, applied left to right (tie-breakers).

| Key | Aliases |
|-----|---------|
| `solver` | `name` |
| `category` | |
| `success` | |
| `time` | `elapsed`, `ms`, `time_ms` |
| `nodes` | `nodes_visited` |
| `guessing` | `guessed` |

```bash
./bin/sudoku compare --file puzzle.txt
./bin/sudoku compare -c search -f puzzle.txt --sort=time,solver
./bin/sudoku compare -c optimization --runs=5 --seed=42 -f puzzle.txt
```

---

### `bench`

Benchmark solvers against every `*.txt` file in a fixture directory and output CSV.

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--fixtures` | | `tests/fixtures` | Directory containing puzzle fixture files |
| `--category` | `-c` | *(all)* | Filter solvers by category |
| `--output` | `-o` | *(stdout)* | Write CSV to this file instead of stdout |

**CSV columns:** `fixture`, `solver`, `category`, `success`, `elapsed_ms`, `nodes_visited`, `used_guessing`.

```bash
./bin/sudoku bench
./bin/sudoku bench --fixtures tests/fixtures -o results.csv
./bin/sudoku bench -c human-logic -o logic-bench.csv
```

---

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

Use any name with `solve --solver=` or filter groups with `compare --category=` / `bench --category=`.

---

## Input format

Puzzles can be provided as:

- A file with 9 lines of 9 characters (`1-9`, `.` or `0` for empty cells)
- A single 81-character string
- Stdin pipe

Example:

```
.4...216.
63..49.5.
.82..1.9.
....5..9.
2...6.54.
4..29.3..
..431..5.
3..9...2.
75..2..1.
```

---

## Difficulty tiers

Used by `generate` and `solve --generate`.

| Tier | Target clues | Minimum clues |
|------|-------------|---------------|
| Easy | 40 | 36 |
| Medium | 34 | 30 |
| Hard | 28 | 24 |

---

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

---

## Architecture

Solvers implement `Sudoku\Solver\SolverInterface`. Additional algorithms can be registered in `SolverRegistry` and are picked up automatically by `compare` and `bench`.
