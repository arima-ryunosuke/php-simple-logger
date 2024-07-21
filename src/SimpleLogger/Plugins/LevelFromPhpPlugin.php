<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Psr\Log\LogLevel;
use ryunosuke\SimpleLogger\Item\Log;

class LevelFromPhpPlugin extends AbstractPlugin
{
    private array $levelMap;

    public function __construct(
        array $levelMap = [
            // emergency
            E_CORE_ERROR        => LogLevel::EMERGENCY,
            // alert
            E_PARSE             => LogLevel::ALERT,
            // critical
            E_COMPILE_ERROR     => LogLevel::CRITICAL,
            // error
            E_ERROR             => LogLevel::ERROR,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_USER_ERROR        => LogLevel::ERROR,
            // warning
            E_CORE_WARNING      => LogLevel::WARNING,
            E_COMPILE_WARNING   => LogLevel::WARNING,
            E_WARNING           => LogLevel::WARNING,
            E_USER_WARNING      => LogLevel::WARNING,
            // notice
            E_NOTICE            => LogLevel::NOTICE,
            E_USER_NOTICE       => LogLevel::NOTICE,
            // info
            E_DEPRECATED        => LogLevel::INFO,
            E_USER_DEPRECATED   => LogLevel::INFO,
            // debug
            E_STRICT            => LogLevel::DEBUG,
        ]
    ) {
        $this->levelMap = $levelMap;
    }

    public function apply(Log $log): ?Log
    {
        if (is_int($log->level)) {
            // some constants conflict with syslog levels, so its handle with negative numbers
            $log->level = $this->levelMap[$log->level] ?? $this->levelMap[-$log->level] ?? $log->level;
        }
        return $log;
    }
}
