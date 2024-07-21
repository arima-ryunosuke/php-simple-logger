<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LevelUnsetPlugin;
use ryunosuke\Test\AbstractTestCase;

class LevelUnsetPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LevelUnsetPlugin();
        $log    = new Log('debug', 'message', []);
        that($plugin)->apply($log)->isSame($log);
        that($log)->arrayize(false, false)->is(['message' => 'message']);
    }
}
