<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\MessageRewritePlugin;
use ryunosuke\Test\AbstractTestCase;

class MessageRewritePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new MessageRewritePlugin("prefix%% %s %%suffix");
        that($plugin)->apply(new Log('debug', 'message', []))->message->is("prefix% message %suffix");

        $plugin = new MessageRewritePlugin(fn($message) => "prefix% {$message} %suffix");
        that($plugin)->apply(new Log('debug', 'message', []))->message->is("prefix% message %suffix");
    }
}
