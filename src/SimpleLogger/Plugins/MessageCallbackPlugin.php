<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;

class MessageCallbackPlugin extends AbstractPlugin
{
    public function __construct()
    {
        // stub
    }

    public function apply(Log $log): ?Log
    {
        if ($this->isCallback($log->message)) {
            // autowiring in future scope
            $log->message = ($log->message)($log);
        }

        return $log;
    }

    private function isCallback($callback): bool
    {
        if (!is_callable($callback)) {
            return false;
        }

        if (is_string($callback) && strpos($callback, '::') === false) {
            return false;
        }

        return true;
    }
}
