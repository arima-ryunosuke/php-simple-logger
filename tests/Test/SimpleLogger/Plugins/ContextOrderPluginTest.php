<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ContextOrderPlugin;
use ryunosuke\Test\AbstractTestCase;

class ContextOrderPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ContextOrderPlugin(['a', 'b', 'c']);
        that($plugin)->apply(new Log('debug', 'message', []))->order->is([
            "a" => "a",
            "b" => "b",
            "c" => "c",
        ]);
    }
}
