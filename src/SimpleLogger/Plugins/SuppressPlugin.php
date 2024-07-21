<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;
use Throwable;

class SuppressPlugin extends AbstractPlugin
{
    private int     $aggregationTime;
    private string  $rememberLocation;
    private Closure $keyProvider;

    private array $suppressions;

    public function __construct(float $aggregationTime, ?string $rememberLocation = null, ?Closure $keyProvider = null)
    {
        $this->aggregationTime  = $aggregationTime;
        $this->rememberLocation = $rememberLocation ?? sys_get_temp_dir() . '/logger-suppressions.txt';
        $this->keyProvider      = $keyProvider ?? static function (Log $log) {
            return sprintf('%s:%s(%s)', $log->level, $log->message, json_encode($log->context));
        };

        try {
            $this->suppressions = @(include $this->rememberLocation) ?: [];
        }
        catch (Throwable) {
            $this->suppressions = [];
        }
    }

    public function __destruct()
    {
        $aggregations = array_filter($this->suppressions, fn($time) => (microtime(true) - $time) < $this->aggregationTime);

        file_put_contents($this->rememberLocation, "<?php return " . var_export($aggregations, true) . ";", LOCK_EX);
    }

    public function apply(Log $log): ?Log
    {
        $aggkey = ($this->keyProvider)($log);
        if ($aggkey !== null) {
            if ((microtime(true) - ($this->suppressions[$aggkey] ?? 0)) < $this->aggregationTime) {
                return null;
            }
            $this->suppressions[$aggkey] = microtime(true);
        }

        return $log;
    }
}
