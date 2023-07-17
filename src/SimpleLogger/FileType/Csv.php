<?php

namespace ryunosuke\SimpleLogger\FileType;

class Csv extends AbstractFileType
{
    private $buffer;

    private string $delimiter;

    public function __construct(string $delimiter)
    {
        $this->buffer    = fopen('php://memory', 'r+b');
        $this->delimiter = $delimiter;
    }

    public function getFlags(): int
    {
        return self::FLAG_STRUCTURE | self::FLAG_COMPLETION;
    }

    public function encode(array $logdata): string
    {
        rewind($this->buffer);
        $byte = fputcsv($this->buffer, $logdata, $this->delimiter);
        rewind($this->buffer);

        return fread($this->buffer, $byte);
    }
}
