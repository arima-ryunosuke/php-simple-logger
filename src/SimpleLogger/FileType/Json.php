<?php

namespace ryunosuke\SimpleLogger\FileType;

class Json extends AbstractFileType
{
    public function getFlags(): int
    {
        return self::FLAG_DATATYPE | self::FLAG_STRUCTURE | self::FLAG_NESTING;
    }

    public function encode(array $logdata): string
    {
        return json_encode($logdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
