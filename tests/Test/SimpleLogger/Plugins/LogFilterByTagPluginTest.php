<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LogFilterByTagPlugin;
use ryunosuke\Test\AbstractTestCase;

class LogFilterByTagPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LogFilterByTagPlugin(['hoge', 'fuga'], false);
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'hoge']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'fuga']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'piyo']))->isNull();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => '']))->isNull();

        $plugin = new LogFilterByTagPlugin(['hoge', 'fuga'], true);
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'hoge']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'fuga']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'piyo']))->isNull();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => '']))->isObject();
    }
}
