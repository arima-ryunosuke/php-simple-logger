<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class ContextFilterPlugin extends AbstractPlugin
{
    private Closure $condition;

    public function __construct(callable $condition)
    {
        $this->condition = Closure::fromCallable($condition);
    }

    public function apply(Log $log): ?Log
    {
        foreach ($log->context as $key => $value) {
            $newvalue = ($this->condition)($value, $key);
            if ($newvalue === null) {
                unset($log->context[$key]);
            }
            else {
                $log->context[$key] = $newvalue;
            }
        }

        return $log;
    }
}
