<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class LevelFilterPlugin extends AbstractPlugin
{
    private int $lowLevel;
    private int $highLevel;

    public function __construct(/*int|string|array*/ $logLevel)
    {
        if (!is_array($logLevel)) {
            $logLevel = [$logLevel, 'EMERGENCY'];
        }

        // 0:EMERGENCY ~ 7:DEBUG
        $this->lowLevel  = Log::levelAsInt($logLevel[1] ?? $logLevel[0]);
        $this->highLevel = Log::levelAsInt($logLevel[0]);
    }

    public function apply(Log $log): ?Log
    {
        $level = Log::levelAsInt($log->level);
        if ($this->lowLevel <= $level && $level <= $this->highLevel) {
            return $log;
        }

        return null;
    }
}
