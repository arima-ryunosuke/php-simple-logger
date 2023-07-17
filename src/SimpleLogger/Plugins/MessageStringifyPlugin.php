<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class MessageStringifyPlugin extends AbstractPlugin
{
    private Closure $structureDumper;
    private int     $structureMaxLength;

    public function __construct(?callable $structureDumper = null, int $structureMaxLength = 1024 * 10)
    {
        $this->structureDumper    = Closure::fromCallable($structureDumper ?? 'var_dump');
        $this->structureMaxLength = $structureMaxLength;
    }

    public function apply(Log $log): ?Log
    {
        if (is_string($log->message) || (is_object($log->message) && method_exists($log->message, '__toString'))) {
            return $log;
        }

        if (is_resource($log->message)) {
            $log->message = get_resource_type($log->message) . ' ' . $log->message;
        }
        elseif (is_array($log->message) || is_object($log->message)) {
            ob_start(fn() => null, $this->structureMaxLength);
            ($this->structureDumper)($log->message);
            $log->message = rtrim(ob_get_clean());
        }
        else {
            $log->message = var_export($log->message, true);
        }

        return $log;
    }
}
