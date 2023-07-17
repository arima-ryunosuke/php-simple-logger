<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Psr\Log\LoggerInterface;
use ryunosuke\SimpleLogger\Item\Log;

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
                    'file'     => $traces[$caller + 1]['file'] ?? $traces[$n - 1]['file'] ?? null,
                    'line'     => $traces[$caller + 1]['line'] ?? $traces[$n - 1]['line'] ?? null,
                    'class'    => $traces[$caller]['class'] ?? $traces[$n - 1]['class'] ?? null,
                    'type'     => $traces[$caller]['type'] ?? $traces[$n - 1]['type'] ?? null,
                    'function' => $traces[$caller]['function'] ?? $traces[$n - 1]['function'] ?? null,
                ];
                $log->context = Log::arrayPickup($extra, $this->entryMap) + $log->context;
                break;
            }
            if (isset($trace['class'], $trace['function'])) {
                if (isset($this->stopClassMethods["{$trace['class']}::{$trace['function']}"])) {
                    $fixed = true;
                }
                if (!$fixed) {
                    $caller = $n;
                }
            }
        }

        return $log;
    }
}
