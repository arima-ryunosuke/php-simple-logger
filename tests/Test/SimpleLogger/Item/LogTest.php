<?php

namespace ryunosuke\Test\SimpleLogger\Item;

use Exception;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\Test\AbstractTestCase;
use stdClass;

class LogTest extends AbstractTestCase
{
    function test_interpolate()
    {
        // use level,context
        $log                   = new Log('debug', '{level} test {data1} message {data2}', ['data1' => 'DATA1']);
        $log->context['data2'] = 'DATA2';
        that($log)->interpolate()->message->is('debug test DATA1 message DATA2');

        // undefined
        $log = new Log('debug', '{level} test {undefined} message', ['data1' => 'DATA1']);
        that($log)->interpolate()->message->is('debug test {undefined} message');

        // null/bool
        $log = new Log('debug', '{level} test {null}/{bool} message', ['null' => null, 'bool' => false]);
        that($log)->interpolate()->message->is('debug test NULL/false message');

        // array
        $log = new Log('debug', '{level} test {array.data1},{array.data2} message', ['array' => ['data1' => 'DATA1', 'data2' => 'DATA2']]);
        that($log)->interpolate()->message->is('debug test DATA1,DATA2 message');

        // stringable object
        $log = new Log('debug', '{level} test {stdclass} {stringable} message', [
            'stdclass'   => new stdClass(),
            'stringable' => new class { public function __toString() { return "stringed"; } },
        ]);
        that($log)->interpolate()->message->is('debug test {stdclass} stringed message');

        // consumption
        $log = new Log('debug', '{level} test {a.b.c}, {A.B.C} message', ['a' => ['b' => ['c' => 'z']], 'A' => ['B' => ['C' => 'y', 'z']]]);
        $log->setFilterConsumption(true);
        that($log)->interpolate()->message->is('debug test z, y message');
        that($log)->interpolate()->context->is(['A' => ['B' => ['z']]]);

        // exception message
        $log = new Log('debug', $e = new Exception(), []);
        that($log)->interpolate()->message->isSame($e);
    }

    function test_arrayize()
    {
        // use level
        $log = new Log('debug', '{level} test {data1} message {data2}', ['data1' => 'DATA1']);
        that($log)->arrayize(false, false)->isSame([
            "data1"   => "DATA1",
            "level"   => "debug",
            "message" => "{level} test {data1} message {data2}",
        ]);

        // stringable
        $log                   = new Log('debug', '{level} test {array}', []);
        $log->context['array'] = [1, 2, 3];
        that($log)->arrayize(true, false)->isSame([
            "level"   => "debug",
            "message" => "{level} test {array}",
        ]);

        // datatype
        $log                  = new Log('debug', '{level} test {null}', []);
        $log->context['null'] = null;
        that($log)->arrayize(true, true)->isSame([
            "null"    => "NULL",
            "level"   => "debug",
            "message" => "{level} test {null}",
        ]);

        // order
        $log = new Log('debug', '{level} test {data1} message', ['data1' => 'DATA1']);
        $log->setOrder(['message', 'data1', 'undefine', 'level']);
        that($log)->arrayize(false, false)->isSame([
            "message" => "{level} test {data1} message",
            "data1"   => "DATA1",
            "level"   => "debug",
        ]);

        // unset level
        $log = new Log('debug', '{level} test {data1} message', ['data1' => 'DATA1']);
        $log->setLevelUnset(true);
        that($log)->arrayize(false, false)->isSame([
            "data1"   => "DATA1",
            "message" => "{level} test {data1} message",
        ]);
    }

    function test_lconv()
    {
        that(Log::class)::levelAsInt('debug')->is(7);
        that(Log::class)::levelAsString('debug')->is('debug');

        that(Log::class)::levelAsInt('INFO')->is(6);
        that(Log::class)::levelAsString('INFO')->is('INFO');

        that(Log::class)::levelAsInt('notice')->is(5);
        that(Log::class)::levelAsString('notice')->is('notice');

        that(Log::class)::levelAsInt('hoge')->wasThrown('is not defined');
        that(Log::class)::levelAsString('hoge')->is('hoge');

        that(Log::class)::levelAsInt(8)->is(8);
        that(Log::class)::levelAsString(8)->wasThrown('is not defined');
    }
}
