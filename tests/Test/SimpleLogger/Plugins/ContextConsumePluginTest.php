<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ContextConsumePlugin;
use ryunosuke\Test\AbstractTestCase;

class ContextConsumePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ContextConsumePlugin();
        $log    = new Log('debug', 'message{context}', ['context' => 'C', 'dummy' => 'D']);
        that($plugin)->apply($log)->isSame($log);
        that($log)->interpolate()->context->is(['dummy' => 'D']);
    }
}
