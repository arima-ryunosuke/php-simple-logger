<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class ContextAppendPlugin extends AbstractPlugin
{
    private array $appendix;

    public function __construct(array $appendix)
    {
        $this->appendix = $appendix;
    }

    public function apply(Log $log): ?Log
    {
        foreach ($this->appendix as $key => $value) {
            if ($value instanceof Closure) {
                $value = $value($log);

                // static closure fixes value
                if (!@$this->appendix[$key]->bindTo($this)) {
                    $this->appendix[$key] = $value;
                }
            }
            $log->context[$key] = $value;
        }

        return $log;
    }
}
