<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LevelNormalizePlugin;
use ryunosuke\Test\AbstractTestCase;

class LevelNormalizePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LevelNormalizePlugin();
        that($plugin)->apply(new Log('debug', 'message', []))->level->is('debug');
        that($plugin)->apply(new Log('Info', 'message', []))->level->is('Info');
        that($plugin)->apply(new Log('NOTICE', 'message', []))->level->is('NOTICE');

        $plugin = new LevelNormalizePlugin(false);
        that($plugin)->apply(new Log('debug', 'message', []))->level->is('debug');
        that($plugin)->apply(new Log('Info', 'message', []))->level->is('info');
        that($plugin)->apply(new Log('NOTICE', 'message', []))->level->is('notice');

        $plugin = new LevelNormalizePlugin(true);
        that($plugin)->apply(new Log('debug', 'message', []))->level->is('DEBUG');
        that($plugin)->apply(new Log('Info', 'message', []))->level->is('INFO');
        that($plugin)->apply(new Log('NOTICE', 'message', []))->level->is('NOTICE');

        $plugin = new LevelNormalizePlugin(false);
        that($plugin)->apply(new Log(4, 'message', []))->level->is('warning');
        that($plugin)->apply(new Log(3, 'message', []))->level->is('error');
        $plugin = new LevelNormalizePlugin(true);
        that($plugin)->apply(new Log(2, 'message', []))->level->is('CRITICAL');
        that($plugin)->apply(new Log(1, 'message', []))->level->is('ALERT');

        that($plugin)->apply(new Log("hoge", 'message', []))->level->is('HOGE');
        that($plugin)->apply(new Log(99, 'message', []))->wasThrown('is not defined');
    }
}
