<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use Throwable;

class ThrowableManglePlugin extends AbstractPlugin
{
    private bool   $asString;
    private string $chainKey;

    public function __construct(bool $asString, string $chainKey = 'chains')
    {
        $this->asString = $asString;
        $this->chainKey = $chainKey;
    }

    public function apply(Log $log): ?Log
    {
        // direct throwable message
        if ($log->message instanceof Throwable) {
            $log->context = $this->convert($log->message) + $log->context;
        }

        // context throwable
        if (($log->context['exception'] ?? null) instanceof Throwable) {
            $log->context = $this->convert($log->context['exception']) + $log->context;
        }

        return $log;
    }

    private function convert(Throwable &$throwable): array
    {
        $result = [];
        if ($this->asString) {
            // file(line) -> file:line
            $throwable = preg_replace('@^(#\d+ )(.+?)\((\d+)\)@m', '$1$2:$3', (string) $throwable);
        }
        else {
            if (strlen($this->chainKey)) {
                // do reverse because string cast same too
                $result[$this->chainKey] = array_reverse($this->mangle($throwable));
            }

            $throwable = $throwable->getMessage();
        }
        return $result;
    }

    private function mangle(Throwable $throwable): array
    {
        return array_merge([
            [
                'class'   => get_class($throwable),
                'message' => $throwable->getMessage(),
                'code'    => $throwable->getCode(),
                'trace'   => array_merge([
                    [
                        'file'     => $throwable->getFile(),
                        'line'     => $throwable->getLine(),
                        'function' => '__construct',
                        'class'    => get_class($throwable),
                        'type'     => '::',
                    ],
                ], $throwable->getTrace()),
            ],
        ], $throwable->getPrevious() ? $this->mangle($throwable->getPrevious()) : []);
    }
}
