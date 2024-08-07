<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use Throwable;

class LevelAutoPlugin extends AbstractPlugin
{
    private array $levelMap;

    public function __construct(array $levelMap = Log::LOG_PHP)
    {
        $this->levelMap = $levelMap;
    }

    public function apply(Log $log): ?Log
    {
        if ($log->level === null) {
            if (($t = $log->message) instanceof Throwable && method_exists($t, 'getSeverity') && is_int($t->getSeverity())) {
                $log->level = $this->levelMap[$t->getSeverity()] ?? null;
            }
            if (($t = $log->context['exception'] ?? null) instanceof Throwable && method_exists($t, 'getSeverity') && is_int($t->getSeverity())) {
                $log->level = $this->levelMap[$t->getSeverity()] ?? null;
            }
            if (isset($log->context['level'])) {
                if (is_int($log->context['level'])) {
                    $log->level = isset(Log::LOG_LABELS[$log->context['level']]) ? $log->context['level'] : null;
                }
                if (is_string($log->context['level'])) {
                    $log->level = isset(Log::LOG_LEVELS[$log->context['level']]) ? $log->context['level'] : null;
                }
            }
        }
        return $log;
    }
}
