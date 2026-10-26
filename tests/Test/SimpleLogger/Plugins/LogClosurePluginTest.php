<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LogClosurePlugin;
use ryunosuke\Test\AbstractTestCase;

class LogClosurePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LogClosurePlugin(function (Log $log) {
            $log->message = 'rewritten';
            return $log;
        });
        that($plugin)->apply(new Log('debug', 'message', []))->message->is('rewritten');
    }
}
