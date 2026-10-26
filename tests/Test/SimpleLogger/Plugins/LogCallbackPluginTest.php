<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LogCallbackPlugin;
use ryunosuke\Test\AbstractTestCase;

class LogCallbackPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LogCallbackPlugin(false);
        that($plugin)->apply(new Log('debug', [$this, 'method'], []))->message->is("this is method");
        that($plugin)->apply(new Log('debug', self::class . "::method", []))->message->is("this is method");
        that($plugin)->apply(new Log('debug', fn(Log $log) => "closure: {$log->context['a']}", ['a' => 'A']))->message->is("closure: A");
        that($plugin)->apply(new Log('debug', "strval", []))->message->is("strval");
        that($plugin)->apply(new Log('debug', [1, 2, 3], []))->message->is([1, 2, 3]);

        $plugin = new LogCallbackPlugin(true);
        that($plugin)->apply(new Log('debug', 'message', [
            'dummy'    => 'dummy',
            'callable' => 'strlen',
            'closure'  => fn($log) => "this is closure({$log->level})",
            'nest'     => [
                'closure' => fn($log) => "this is closure({$log->message})",
            ],
        ]))->context->is([
            'dummy'    => 'dummy',
            "callable" => "strlen",
            "closure"  => "this is closure(debug)",
            "nest"     => [
                "closure" => "this is closure(message)",
            ],
        ]);
    }

    static function method()
    {
        return 'this is method';
    }
}
