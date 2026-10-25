<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class MessageInterpolatePlugin extends AbstractPlugin
{
    private bool $interpolation;

    public function __construct(bool $interpolation)
    {
        $this->interpolation = $interpolation;
    }

    public function apply(Log $log): ?Log
    {
        return $log->setMessageInterpolation($this->interpolation);
    }
}
