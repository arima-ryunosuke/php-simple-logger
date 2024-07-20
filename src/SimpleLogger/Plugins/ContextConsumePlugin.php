<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class ContextConsumePlugin extends AbstractPlugin
{
    public function __construct()
    {
        // stub
    }

    public function apply(Log $log): ?Log
    {
        return $log->setFilterConsumption(true);
    }
}
