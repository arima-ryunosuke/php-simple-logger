<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ContextFilterPlugin;
use ryunosuke\Test\AbstractTestCase;

class ContextFilterPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ContextFilterPlugin(function ($value, $key) {
            if ($key === 'unset') {
                return null;
            }
            return mb_strimwidth($value, 0, 10, '...');
        });
        that($plugin)->apply(new Log('debug', 'message', [
            'unset'   => 'hoge',
            'string1' => '1234567890abcdef',
            'string2' => '1234567890',
        ]))->context->is([
            "string1" => "1234567...",
            "string2" => "1234567890",
        ]);
    }
}
