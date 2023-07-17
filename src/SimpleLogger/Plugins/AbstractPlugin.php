<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

abstract class AbstractPlugin
{
    /**
     * apply plugin to log
     *
     * you may rewrite $log as you wish.
     * if return null, that log is skipped.
     *
     * @param Log $log
     * @return ?Log
     */
    abstract public function apply(Log $log): ?Log;
}
