<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use Psr\Log\LogLevel;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LevelFromPhpPlugin;
use ryunosuke\Test\AbstractTestCase;

class LevelFromPhpPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new LevelFromPhpPlugin();
        that($plugin)->apply(new Log(E_CORE_ERROR, 'message', []))->level->is(LogLevel::EMERGENCY);
        that($plugin)->apply(new Log(E_PARSE, 'message', []))->level->is(LogLevel::ALERT);
        that($plugin)->apply(new Log(E_COMPILE_ERROR, 'message', []))->level->is(LogLevel::CRITICAL);
        that($plugin)->apply(new Log(E_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(E_RECOVERABLE_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(E_USER_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(E_CORE_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(E_COMPILE_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(E_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(E_USER_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(E_NOTICE, 'message', []))->level->is(LogLevel::NOTICE);
        that($plugin)->apply(new Log(E_USER_NOTICE, 'message', []))->level->is(LogLevel::NOTICE);
        that($plugin)->apply(new Log(E_DEPRECATED, 'message', []))->level->is(LogLevel::INFO);
        that($plugin)->apply(new Log(E_USER_DEPRECATED, 'message', []))->level->is(LogLevel::INFO);
        that($plugin)->apply(new Log(E_STRICT, 'message', []))->level->is(LogLevel::DEBUG);
        that($plugin)->apply(new Log(-E_CORE_ERROR, 'message', []))->level->is(LogLevel::EMERGENCY);
        that($plugin)->apply(new Log(-E_PARSE, 'message', []))->level->is(LogLevel::ALERT);
        that($plugin)->apply(new Log(-E_COMPILE_ERROR, 'message', []))->level->is(LogLevel::CRITICAL);
        that($plugin)->apply(new Log(-E_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(-E_RECOVERABLE_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(-E_USER_ERROR, 'message', []))->level->is(LogLevel::ERROR);
        that($plugin)->apply(new Log(-E_CORE_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(-E_COMPILE_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(-E_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(-E_USER_WARNING, 'message', []))->level->is(LogLevel::WARNING);
        that($plugin)->apply(new Log(-E_NOTICE, 'message', []))->level->is(LogLevel::NOTICE);
        that($plugin)->apply(new Log(-E_USER_NOTICE, 'message', []))->level->is(LogLevel::NOTICE);
        that($plugin)->apply(new Log(-E_DEPRECATED, 'message', []))->level->is(LogLevel::INFO);
        that($plugin)->apply(new Log(-E_USER_DEPRECATED, 'message', []))->level->is(LogLevel::INFO);
        that($plugin)->apply(new Log(-E_STRICT, 'message', []))->level->is(LogLevel::DEBUG);

        that($plugin)->apply(new Log('debug', 'message', []))->level->is('debug');
        that($plugin)->apply(new Log('Info', 'message', []))->level->is('Info');
        that($plugin)->apply(new Log('NOTICE', 'message', []))->level->is('NOTICE');
    }
}
