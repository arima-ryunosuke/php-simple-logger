<?php

namespace ryunosuke\SimpleLogger\Plugins;

use Closure;
use ryunosuke\SimpleLogger\Item\Log;

class MessageRewritePlugin extends AbstractPlugin
{
    private Closure $rewriter;

    public function __construct(/*string|callable*/ $rewriter)
    {
        if (is_string($rewriter)) {
            $rewriter = fn($message) => sprintf($rewriter, $message);
        }
        $this->rewriter = $rewriter;
    }

    public function apply(Log $log): ?Log
    {
        if (is_string($log->message)) {
            $log->message = ($this->rewriter)($log->message);
        }

        return $log;
    }
}
