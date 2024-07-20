<?php

namespace ryunosuke\Test\SimpleLogger;

use ArrayLogger;
use ReflectionClass;
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
        $logger->prependPlugin(new MessageRewritePlugin(static fn($v) => strtoupper($v)));
        $logger->replacePlugins(new MessageRewritePlugin(static fn($v) => strtolower($v)));

        $logger->setPlugins($logger->getPlugins() + [
                'a1' => new class extends AbstractPlugin {
                    public function apply(Log $log): ?Log { return $log; }
                },
                'a2' => new class extends AbstractPlugin {
                    public function apply(Log $log): ?Log { return $log; }
                },
            ]);

        $logger->sortPlugins(function (AbstractPlugin $plugin, string $key) {
            if ($key === 'a1') {
                return 0;
            }
            if ($key === 'a2') {
                return 999;
            }
            if ((new ReflectionClass($plugin))->isAnonymous()) {
                return 499;
            }
            return 500;
        });

        $plugins = $logger->getPlugins();
        that($plugins)->count(4);
        that(array_keys($plugins))->is(['a1', 1, 0, 'a2']);

        $logger->debug('ok1');
        $logger->info('ng1');
        $logger->notice('ok2');
        $logger->warning('ng2');

        that(array_column($logs, 'message'))->is(['ok1', 'ok2']);
    }
}
