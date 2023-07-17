<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\MessageCallbackPlugin;
use ryunosuke\Test\AbstractTestCase;

class MessageCallbackPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new MessageCallbackPlugin();
        that($plugin)->apply(new Log('debug', [$this, 'method'], []))->message->is("this is method");
        that($plugin)->apply(new Log('debug', self::class . "::method", []))->message->is("this is method");
        that($plugin)->apply(new Log('debug', fn(Log $log) => "closure: {$log->context['a']}", ['a' => 'A']))->message->is("closure: A");
        that($plugin)->apply(new Log('debug', "strval", []))->message->is("strval");
        that($plugin)->apply(new Log('debug', [1, 2, 3], []))->message->is([1, 2, 3]);
    }

    static function method()
    {
        return 'this is method';
    }
}
