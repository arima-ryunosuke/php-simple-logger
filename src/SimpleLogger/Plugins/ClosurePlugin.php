<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class ClosurePlugin extends AbstractPlugin
{
    private Closure $closure;

    public function __construct(Closure $closure)
    {
        return $this->closure = $closure;
    }

    public function apply(Log $log): ?Log
    {
        return ($this->closure)($log);
    }
}
