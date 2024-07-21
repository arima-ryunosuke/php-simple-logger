<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\SuppressPlugin;
use ryunosuke\Test\AbstractTestCase;

class SuppressPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new SuppressPlugin(1, sys_get_temp_dir() . '/agg1.txt');

        that($plugin)->apply(new Log('debug', 'message', ['hoge' => '1']))->isNotNull();
        that($plugin)->apply(new Log('debug', 'message', ['hoge' => '1']))->isNull();
        that($plugin)->apply(new Log('debug', 'message', ['hoge' => '2']))->isNotNull();
        that($plugin)->apply(new Log('debug', 'message', ['hoge' => '2']))->isNull();

        that($plugin)->apply(new Log('debug', 'message-{hoge}', ['hoge' => '1']))->isNotNull();
        that($plugin)->apply(new Log('debug', 'message-{hoge}', ['hoge' => '1']))->isNull();
        that($plugin)->apply(new Log('debug', 'message-{hoge}', ['hoge' => '2']))->isNotNull();
        that($plugin)->apply(new Log('debug', 'message-{hoge}', ['hoge' => '2']))->isNull();

        sleep(1);
        that($plugin)->apply(new Log('debug', 'message-{hoge}', ['hoge' => '1']))->isNotNull();
    }

    function test_lifecycle()
    {
        $log = sys_get_temp_dir() . '/agg2.txt';
        file_put_contents($log, '<?php hoge');
        $plugin = new SuppressPlugin(1, $log);

        that($plugin)->apply(new Log('debug', 'message1', []))->isNotNull();
        sleep(1);
        that($plugin)->apply(new Log('debug', 'message2', []))->isNotNull();

        unset($plugin);
        gc_collect_cycles();

        that($log)->fileNotContains('message1');
        that($log)->fileContains('message2');
    }
}
