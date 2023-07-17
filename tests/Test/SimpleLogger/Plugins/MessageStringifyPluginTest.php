<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\MessageStringifyPlugin;
use ryunosuke\Test\AbstractTestCase;

class MessageStringifyPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new MessageStringifyPlugin('print_r');
        that($plugin)->apply(new Log('debug', null, []))->message->is("NULL");
        that($plugin)->apply(new Log('debug', true, []))->message->is("true");
        that($plugin)->apply(new Log('debug', 3.14, []))->message->is("3.14");
        that($plugin)->apply(new Log('debug', STDOUT, []))->message->is("stream Resource id #2");
        that($plugin)->apply(new Log('debug', [1, 2, 3], []))->message->is(<<<OUT
        Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )
        OUT,);
        that($plugin)->apply(new Log('debug', (object) [1, 2, 3], []))->message->is(<<<OUT
        stdClass Object
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )
        OUT,);
    }
}
