<?php

declare(strict_types=1);

namespace Sudoku\Core;

final class BoardParser
{
    public function parse(string $input): Board
    {
        $normalized = trim($input);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Input is empty.');
        }

        $lines = preg_split('/\R/', $normalized) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        if (count($lines) === 1 && strlen($lines[0]) === 81) {
            return $this->parseFlat($lines[0]);
        }

        if (count($lines) === Board::SIZE) {
            return $this->parseLines($lines);
        }

        throw new \InvalidArgumentException('Input must be 9 lines of 9 characters or a single 81-character string.');
    }

    public function parseFile(string $path): Board
    {
        if (!is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Cannot read file: %s', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read file: %s', $path));
        }

        return $this->parse($contents);
    }

    public function parseStdin(): Board
    {
        $contents = stream_get_contents(STDIN);

        if ($contents === false || trim($contents) === '') {
            throw new \InvalidArgumentException('No puzzle provided on stdin.');
        }

        return $this->parse($contents);
    }

    /**
     * @param list<string> $lines
     */
    private function parseLines(array $lines): Board
    {
        $cells = [];

        foreach ($lines as $rowIndex => $line) {
            $line = str_replace(' ', '', $line);

            if (strlen($line) !== Board::SIZE) {
                throw new \InvalidArgumentException(sprintf('Row %d must contain 9 characters.', $rowIndex + 1));
            }

            $cells[$rowIndex] = [];

            for ($col = 0; $col < Board::SIZE; ++$col) {
                $cells[$rowIndex][$col] = $this->parseChar($line[$col], $rowIndex, $col);
            }
        }

        return new Board($cells);
    }

    private function parseFlat(string $flat): Board
    {
        $cells = [];

        for ($row = 0; $row < Board::SIZE; ++$row) {
            $cells[$row] = [];

            for ($col = 0; $col < Board::SIZE; ++$col) {
                $index = $row * Board::SIZE + $col;
                $cells[$row][$col] = $this->parseChar($flat[$index], $row, $col);
            }
        }

        return new Board($cells);
    }

    private function parseChar(string $char, int $row, int $col): int
    {
        if ($char === '.' || $char === '0') {
            return 0;
        }

        if ($char >= '1' && $char <= '9') {
            return (int) $char;
        }

        throw new \InvalidArgumentException(sprintf('Invalid character %s at row %d, col %d.', $char, $row + 1, $col + 1));
    }
}
