<?php

namespace ryunosuke\SimpleLogger\FileType;

class Csv extends AbstractFileType
{
    private $buffer;

    private string $delimiter;
    /*readonly*/
    public bool $withHeader;

    public function __construct(string $delimiter, bool $withHeader = false)
    {
        $this->buffer     = fopen('php://memory', 'r+b');
        $this->delimiter  = $delimiter;
        $this->withHeader = $withHeader;
    }

    public function getFlags(): int
    {
        return self::FLAG_STRUCTURE | self::FLAG_COMPLETION;
    }

    public function head(array $logdata): string
    {
        return $this->withHeader ? $this->encode(array_keys($logdata)) : '';
    }

    public function encode(array $logdata): string
    {
        rewind($this->buffer);
        $byte = fputcsv($this->buffer, $logdata, $this->delimiter);
        rewind($this->buffer);

        return fread($this->buffer, $byte);
    }
}
