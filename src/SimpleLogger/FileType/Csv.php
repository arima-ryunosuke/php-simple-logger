<?php

namespace ryunosuke\SimpleLogger\FileType;

class Csv extends AbstractFileType
{
    private       $buffer;
    private array $positions;

    private string $delimiter;
    /*readonly*/
    public bool $withHeader;
    public ?int $maxAlignment;

    public function __construct(string $delimiter, bool $withHeader = true, ?int $maxAlignment = null)
    {
        $this->buffer       = fopen('php://memory', 'r+b');
        $this->positions    = [];
        $this->delimiter    = $delimiter;
        $this->withHeader   = $withHeader;
        $this->maxAlignment = $maxAlignment;
    }

    public function getFlags(): int
    {
        return self::FLAG_STRUCTURE | self::FLAG_COMPLETION;
    }

    public function head(array $logdata): string
    {
        $this->positions = [];
        return $this->withHeader ? $this->encode(array_keys($logdata)) : '';
    }

    public function encode(array $logdata): string
    {
        if ($this->maxAlignment !== null) {
            $logmeta = array_values(array_map(function ($value) {
                rewind($this->buffer);
                $byte = fputcsv($this->buffer, [$value], ",");
                rewind($this->buffer);

                $value = rtrim(fread($this->buffer, $byte));

                $lines = preg_split('@\r\n|\n@u', $value);
                $first = $lines[array_key_first($lines)];
                $last  = $lines[array_key_last($lines)];

                return [$value, $lines, $first, $last];
            }, $logdata));

            $fields   = [];
            $position = 0;
            $column   = 0;
            foreach ($logmeta as $i => [$value, $lines, $first, $last]) {
                $delimiter_length = $column * strlen($this->delimiter);
                $padding_length   = ($this->positions[$i] ?? 0) - $position - $delimiter_length;
                if ($padding_length > 0) {
                    if (($value[0] ?? '') !== '"') {
                        $value = '"' . $value . '"';
                    }
                    $value = str_repeat(' ', min($this->maxAlignment, $padding_length)) . $value;
                }
                $fields[] = $value;

                $this->positions[$i] = max($this->positions[$i] ?? 0, $position + $delimiter_length);
                if (count($lines) > 1) {
                    $position = strlen($last);
                    $column   = 0;
                }
                else {
                    $position += strlen($value);
                }
                $column++;
            }

            return implode($this->delimiter, $fields) . "\n";
        }

        rewind($this->buffer);
        $byte = fputcsv($this->buffer, $logdata, $this->delimiter);
        rewind($this->buffer);

        return fread($this->buffer, $byte);
    }
}
