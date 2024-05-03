<?php

namespace ryunosuke\Test\SimpleLogger;

use ArrayLogger;
use JsonSerializable;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\AbstractPlugin;
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
        $logger->prependPlugin(new class extends AbstractPlugin implements JsonSerializable {
            public function apply(Log $log): ?Log
            {
                return $log;
            }

            public function jsonSerialize(): mixed { }
        });

        that($logger)->getPlugins()->count(2);

        $logger->debug('ok1');
        $logger->info('ng1');
        $logger->notice('ok2');
        $logger->warning('ng2');

        that(array_column($logs, 'message'))->is(['ok1', 'ok2']);
    }
}
