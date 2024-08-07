<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ErrorException;
use Exception;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LevelAutoPlugin;
use ryunosuke\Test\AbstractTestCase;

class LevelAutoPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LevelAutoPlugin();
        that($plugin)->apply(new Log(null, 'message', []))->level->is(null);

        that($plugin)->apply(new Log(null, 'message', ['level' => 'hoge']))->level->is(null);
        that($plugin)->apply(new Log(null, 'message', ['level' => 7]))->level->is(7);
        that($plugin)->apply(new Log(null, 'message', ['level' => 'debug']))->level->is('debug');

        that($plugin)->apply(new Log(null, new Exception(), []))->level->is(null);
        that($plugin)->apply(new Log(null, new ErrorException('', 0, E_NOTICE), []))->level->is('notice');

        that($plugin)->apply(new Log(null, 'message', ['exception' => 'hoge']))->level->is(null);
        that($plugin)->apply(new Log(null, 'message', ['exception' => new Exception()]))->level->is(null);
        that($plugin)->apply(new Log(null, 'message', ['exception' => new ErrorException('', 0, E_NOTICE)]))->level->is('notice');

        that($plugin)->apply(new Log('notice', 'message', []))->level->is('notice');
    }
}
