<?php

namespace ryunosuke\SimpleLogger\FileType;

use Symfony\Component\Yaml\Dumper;

class YamlLine extends AbstractFileType
{
    private Dumper $dumper;

    public function __construct()
    {
        $this->dumper = new Dumper();
    }

    public function getFlags(): int
    {
        return self::FLAG_ONELINER | self::FLAG_DATATYPE | self::FLAG_STRUCTURE | self::FLAG_NESTING | self::FLAG_COMPLETION;
    }

    public function encode(array $logdata): string
    {
        return $this->dumper->dump($logdata) . "\n";
    }
}
