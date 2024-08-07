<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class LevelFromPhpPlugin extends AbstractPlugin
{
    private array $levelMap;

    public function __construct(array $levelMap = Log::LOG_PHP)
    {
        $this->levelMap = $levelMap;
    }

    public function apply(Log $log): ?Log
    {
        if (is_int($log->level)) {
            // some constants conflict with syslog levels, so its handle with negative numbers
            $log->level = $this->levelMap[$log->level] ?? $this->levelMap[-$log->level] ?? $log->level;
        }
        return $log;
    }
}
