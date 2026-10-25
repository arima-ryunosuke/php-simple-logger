<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Psr\Log\LoggerInterface;
use ryunosuke\SimpleLogger\Item\Log;

/** @deprecated use ContextAppendLocationPlugin */
class LocationAppendPlugin extends AbstractPlugin
{
    private array $entryMap;
    private array $stopClassMethods;

    public function __construct(array $entryMap = ['file' => 'file', 'line' => 'line'], array $stopClassMethods = [])
    {
        $this->entryMap         = $entryMap;
        $this->stopClassMethods = array_flip(array_map(fn($v) => ltrim($v, "\\"), $stopClassMethods));
    }

    public function apply(Log $log): ?Log
    {
        // not supports call by global function
        $caller = -1;
        $fixed  = false;
        $traces = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT));
        foreach ($traces as $n => $trace) {
            if (($trace['object'] ?? null) instanceof LoggerInterface) {
                $extra = [
                    'file'     => $traces[$caller]['file'] ?? $traces[$n]['file'] ?? null,
                    'line'     => $traces[$caller]['line'] ?? $traces[$n]['line'] ?? null,
                    'class'    => $traces[$caller - 1]['class'] ?? $traces[$n - 1]['class'] ?? null,
                    'type'     => $traces[$caller - 1]['type'] ?? $traces[$n - 1]['type'] ?? null,
                    'function' => $traces[$caller - 1]['function'] ?? $traces[$n - 1]['function'] ?? null,
                ];
                $log->context = array_replace($log->context, Log::arrayPickup($extra, $this->entryMap));
                break;
            }
            if (isset($trace['class'], $trace['function'])) {
                if (isset($this->stopClassMethods["{$trace['class']}::{$trace['function']}"])) {
                    $fixed = true;
                }
                if (!$fixed) {
                    $caller = $n + 1;
                }
            }
        }

        return $log;
    }
}
