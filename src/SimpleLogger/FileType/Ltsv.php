<?php

namespace ryunosuke\SimpleLogger\FileType;

class Ltsv extends AbstractFileType
{
    public function getFlags(): int
    {
        return self::FLAG_ONELINER | self::FLAG_STRUCTURE;
    }

    public function encode(array $logdata): string
    {
        $fields = [];
        foreach ($logdata as $key => $value) {
            $fields[] = "$key:" . self::replaceBreakLine($value, ' ');
        }
        return implode("\t", $fields) . "\n";
    }
}
