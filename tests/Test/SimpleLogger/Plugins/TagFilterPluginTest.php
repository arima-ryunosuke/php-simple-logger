<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\TagFilterPlugin;
use ryunosuke\Test\AbstractTestCase;

class TagFilterPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new TagFilterPlugin(['hoge', 'fuga'], false);
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'hoge']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'fuga']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'piyo']))->isNull();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => '']))->isNull();

        $plugin = new TagFilterPlugin(['hoge', 'fuga'], true);
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'hoge']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'fuga']))->isObject();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => 'piyo']))->isNull();
        that($plugin)->apply(new Log('debug', 'message', ['tag' => '']))->isObject();
    }
}
