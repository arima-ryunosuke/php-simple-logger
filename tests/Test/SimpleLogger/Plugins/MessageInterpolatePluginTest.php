<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\MessageInterpolatePlugin;
use ryunosuke\Test\AbstractTestCase;

class MessageInterpolatePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new MessageInterpolatePlugin(false);
        that($plugin)->apply(new Log('debug', 'a{hoge}z', ['hoge' => 'HOGE']))->interpolate()->message->is('a{hoge}z');

        $plugin = new MessageInterpolatePlugin(true);
        that($plugin)->apply(new Log('debug', 'a{hoge}z', ['hoge' => 'HOGE']))->interpolate()->message->is('aHOGEz');
    }
}
