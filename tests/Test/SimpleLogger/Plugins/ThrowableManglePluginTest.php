<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ArrayObject;
use DomainException;
use Exception;
use RuntimeException;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\ThrowableManglePlugin;
use ryunosuke\Test\AbstractTestCase;

class ThrowableManglePluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $plugin = new ThrowableManglePlugin(false, "key");
        $exlog  = new Log('debug', new Exception('message'), []);
        $cxlog  = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->is("message");
        that($plugin)->apply($exlog)->context['key']->isArray();
        that($plugin)->apply($cxlog)->context['exception']->is('exmsg');

        $plugin = new ThrowableManglePlugin(false, "");
        $exlog  = new Log('debug', new Exception('message'), []);
        $cxlog  = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->is("message");
        that($plugin)->apply($exlog)->context->notHasKey("");
        that($plugin)->apply($cxlog)->context['exception']->is('exmsg');

        $plugin = new ThrowableManglePlugin(true);
        $exlog  = new Log('debug', new Exception('message'), []);
        $cxlog  = new Log('debug', "this {exception}", ['exception' => new Exception('exmsg')]);
        that($plugin)->apply($exlog)->message->contains(__FILE__);
        that($plugin)->apply($exlog)->context->notHasKey("");
        that($plugin)->apply($cxlog)->context['exception']->contains(__FILE__);
    }

    function test_apply_arg()
    {
        $plugin = new ThrowableManglePlugin(false, "key", 10);
        $exlog  = new Log('debug', provideNestException(), []);
        that($plugin)->apply($exlog)->message->is("message1");
        $args = that($exlog->context['key'][0]['trace'][1]['args']);
        $args[0]->is('Object(SensitiveParameterValue)');
        $args[1]->stringStartsWith('Resource');
        $args[2]->is(123);
        $args[3]->is(3.14);
        $args[4]->is(null);
        $args[5]->is(false);
        $args[6]->is('Object(ArrayObject)');
        $args[7]->isArray();
        $args[8]->isString();

        $plugin  = new ThrowableManglePlugin(true, "key", 100);
        $exlog   = new Log('debug', provideNestException(), []);
        $message = that($plugin)->apply($exlog)->message;
        $message->contains("message1");
        $message->contains("message2");
        $message->contains("Object(SensitiveParameterValue)");
        $message->contains("Resource id");
        $message->contains("123");
        $message->contains("3.14");
        $message->contains("null");
        $message->contains("false");
        $message->contains("Object(ArrayObject)");
        $message->contains("10, 11, 12, 13, 14, 15");
    }
}

function provideNestException(): Exception
{
    try {
        return (fn() => (fn() => (fn() => (new class() {
            public function __invoke(
                #[\SensitiveParameter]
                $x
            ) {
                throw new DomainException('message1', 0, new RuntimeException('message2'));
            }
        }
        )('sensitive', ...array_merge(func_get_args(), [new ArrayObject(), range(0, 99), str_repeat('x', 1024 * 2)]))
        )(...array_merge(func_get_args(), [null, false]))
        )(...array_merge(func_get_args(), [123, 3.14]))
        )(STDOUT);
    }
    catch (Exception $t) {
        return $t;
    }
}
