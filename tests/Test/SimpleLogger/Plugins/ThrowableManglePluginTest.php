<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use Exception;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ThrowableManglePlugin;
use ryunosuke\Test\AbstractTestCase;

class ThrowableManglePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ThrowableManglePlugin(false, "key");
        $exlog = new Log('debug', new Exception('message'), []);
        $cxlog = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->is("message");
        that($plugin)->apply($exlog)->context['key']->isArray();
        that($plugin)->apply($cxlog)->context['exception']->is('exmsg');

        $plugin = new ThrowableManglePlugin(false, "");
        $exlog = new Log('debug', new Exception('message'), []);
        $cxlog = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->is("message");
        that($plugin)->apply($exlog)->context->notHasKey("");
        that($plugin)->apply($cxlog)->context['exception']->is('exmsg');

        $plugin = new ThrowableManglePlugin(true);
        $exlog = new Log('debug', new Exception('message'), []);
        $cxlog = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->contains(__FILE__);
        that($plugin)->apply($exlog)->context->notHasKey("");
        that($plugin)->apply($cxlog)->context['exception']->contains(__FILE__);
    }
}
