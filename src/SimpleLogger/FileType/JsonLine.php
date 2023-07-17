<?php

namespace ryunosuke\SimpleLogger\FileType;

class JsonLine extends AbstractFileType
{
    public function getFlags(): int
    {
        return self::FLAG_DATATYPE | self::FLAG_ONELINER | self::FLAG_STRUCTURE | self::FLAG_NESTING;
    }

    public function encode(array $logdata): string
    {
        return json_encode($logdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
