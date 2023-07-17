<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class LevelNormalizePlugin extends AbstractPlugin
{
    private Closure $caseConverter;

    public function __construct(?bool $eitherUpperOrLower = null)
    {
        $this->caseConverter = (function ($eitherUpperOrLower) {
            if ($eitherUpperOrLower === null) {
                return fn($v) => $v;
            }
            if ($eitherUpperOrLower === false) {
                return fn($v) => strtolower($v);
            }
            if ($eitherUpperOrLower === true) {
                return fn($v) => strtoupper($v);
            }
        })($eitherUpperOrLower);
    }

    public function apply(Log $log): ?Log
    {
        $log->level = ($this->caseConverter)(Log::levelAsString($log->level));

        return $log;
    }
}
