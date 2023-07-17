<?php

namespace ryunosuke\SimpleLogger\FileType;

use Symfony\Component\Yaml\Dumper;

class Yaml extends AbstractFileType
{
    private Dumper $dumper;

    public function __construct()
    {
        $this->dumper = new Dumper();
    }

    public function getFlags(): int
    {
        return self::FLAG_DATATYPE | self::FLAG_STRUCTURE | self::FLAG_NESTING | self::FLAG_COMPLETION;
    }

    public function encode(array $logdata): string
    {
        return "---\n" . $this->dumper->dump($logdata, 9999);
    }
}
