<?php

namespace ryunosuke\SimpleLogger\FileType;

class Text extends AbstractFileType
{
    private bool $pretty;

    public function __construct(bool $pretty)
    {
        $this->pretty = $pretty;
    }

    public function getFlags(): int
    {
        return self::FLAG_PLAIN;
    }

    public function encode(array $logdata): string
    {
        if ($this->pretty) {
            $message = (string) $logdata['message'];
            if (!preg_match('#\r?\n\s{2,}#', $message)) {
                $logdata['message'] = self::replaceBreakLine($message, "\n    ");
            }
        }
        return $logdata['message'] . "\n";
    }
}
