<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LevelFilterPlugin;
use ryunosuke\Test\AbstractTestCase;

class LevelFilterPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LevelFilterPlugin('notice');
        that($plugin)->apply(new Log('debug', 'message', []))->isNull();
        that($plugin)->apply(new Log('info', 'message', []))->isNull();
        that($plugin)->apply(new Log('notice', 'message', []))->isObject();
        that($plugin)->apply(new Log(7, 'message', []))->isNull();
        that($plugin)->apply(new Log(6, 'message', []))->isNull();
        that($plugin)->apply(new Log(5, 'message', []))->isObject();

        $plugin = new LevelFilterPlugin([99, 0]);
        that($plugin)->apply(new Log(99, 'message', []))->isObject();
        that($plugin)->apply(new Log("hoge", 'message', []))->wasThrown('is not defined');
    }
}
