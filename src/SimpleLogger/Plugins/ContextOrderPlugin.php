<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class ContextOrderPlugin extends AbstractPlugin
{
    private array $order;

    public function __construct(array $order)
    {
        $this->order = $order;
    }

    public function apply(Log $log): ?Log
    {
        return $log->setOrder($this->order);
    }
}
