<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ClosurePlugin;
use ryunosuke\Test\AbstractTestCase;

class ClosurePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ClosurePlugin(function (Log $log) {
            $log->message = 'rewritten';
            return $log;
        });
        that($plugin)->apply(new Log('debug', 'message', []))->message->is('rewritten');
    }
}
