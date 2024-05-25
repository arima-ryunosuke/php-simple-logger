<?php

namespace ryunosuke\Test\SimpleLogger;

use ArrayLogger;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\AbstractPlugin;
use ryunosuke\SimpleLogger\Plugins\MessageRewritePlugin;
use ryunosuke\Test\AbstractTestCase;

class AbstractLoggerTest extends AbstractTestCase
{
    function test_all()
    {
        $logger = new ArrayLogger($logs);
        $logger->appendPlugin(new class extends AbstractPlugin {
            public function apply(Log $log): ?Log
            {
                return strpos($log->message, "ok") === false ? null : $log;
            }
        });
        $logger->prependPlugin(new MessageRewritePlugin(fn($v) => strtoupper($v)));
        $logger->replacePlugins(new MessageRewritePlugin(fn($v) => strtolower($v)));

        that($logger)->getPlugins()->count(2);

        $logger->debug('ok1');
        $logger->info('ng1');
        $logger->notice('ok2');
        $logger->warning('ng2');

        that(array_column($logs, 'message'))->is(['ok1', 'ok2']);
    }
}
