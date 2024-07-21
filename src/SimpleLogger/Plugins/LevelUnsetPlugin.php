<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class LevelUnsetPlugin extends AbstractPlugin
{
    public function __construct()
    {
        // stub
    }

    public function apply(Log $log): ?Log
    {
        return $log->setLevelUnset(true);
    }
}
