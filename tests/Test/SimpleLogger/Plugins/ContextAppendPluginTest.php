<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ContextAppendPlugin;
use ryunosuke\Test\AbstractTestCase;

class ContextAppendPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $static  = 0;
        $dynamic = 0;
        $plugin  = new ContextAppendPlugin([
            'static'  => static function () use (&$static) { return $static++; },
            'dynamic' => function () use (&$dynamic) { return $dynamic++; },
            'fixed'   => 'fixed',
        ]);
        that($plugin)->apply(new Log('debug', 'message', []))->context->is([
            "static"  => 0,
            "dynamic" => 0,
            "fixed"   => "fixed",
        ]);
        that($plugin)->apply(new Log('debug', 'message', []))->context->is([
            "static"  => 0,
            "dynamic" => 1,
            "fixed"   => "fixed",
        ]);
        that($plugin)->apply(new Log('debug', 'message', []))->context->is([
            "static"  => 0,
            "dynamic" => 2,
            "fixed"   => "fixed",
        ]);
    }
}
