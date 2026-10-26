<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class LogCallbackPlugin extends AbstractPlugin
{
    private bool $closureOnly;

    public function __construct(bool $closureOnly = true)
    {
        $this->closureOnly = $closureOnly;
    }

    public function apply(Log $log): ?Log
    {
        if ($this->isCallback($log->message)) {
            // autowiring in future scope
            $log->message = ($log->message)($log);
        }

        array_walk_recursive($log->context, function (&$value) use ($log) {
            if ($this->isCallback($value)) {
                // autowiring in future scope
                $value = ($value)($log);
            }
        });

        return $log;
    }

    private function isCallback($callback): bool
    {
        if (!is_callable($callback)) {
            return false;
        }

        if ($this->closureOnly && !$callback instanceof Closure) {
            return false;
        }

        if (is_string($callback) && strpos($callback, '::') === false) {
            return false;
        }

        return true;
    }
}
