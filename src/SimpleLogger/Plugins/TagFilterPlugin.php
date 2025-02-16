<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class TagFilterPlugin extends AbstractPlugin
{
    private array $target;
    private bool  $emptyAsAll;

    public function __construct($target, bool $emptyAsAll = false)
    {
        $this->target     = array_filter(array_map('strval', (array) $target), 'strlen');
        $this->emptyAsAll = $emptyAsAll;
    }

    public function apply(Log $log): ?Log
    {
        $tags = array_filter(array_map('strval', (array) ($log->context['tag'] ?? [])), 'strlen');

        if ($this->emptyAsAll && !$tags) {
            return $log;
        }

        if (!array_intersect($this->target, $tags)) {
            return null;
        }

        return $log;
    }
}
