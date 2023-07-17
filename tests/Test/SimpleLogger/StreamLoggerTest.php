<?php

namespace ryunosuke\Test\SimpleLogger;

use ryunosuke\SimpleLogger\StreamLogger;
use ryunosuke\Test\AbstractTestCase;

class StreamLoggerTest extends AbstractTestCase
{
    function test___construct()
    {
        $directory = $this->emptyDirectory();

        $logger = new StreamLogger("file://$directory/file-log.txt", [
            'mode'    => 'a',
            'context' => ['secure' => false], // for converage
        ]);
        that($logger)->metadata['mode']->is('a');
    }

    function test_reopen()
    {
        $directory = $this->emptyDirectory();

        $logger = new StreamLogger("file://$directory/file-log.txt");
        $logger->debug('1');
        $fdid = (int) tmpfile();
        $logger->reopen();
        that((int) tmpfile())->is($fdid + 2);
        $logger->info('2');
        that("file://$directory/file-log.txt")->fileEquals("1\n2\n");
    }

    function test_setPresetPlugins()
    {
        $directory = $this->emptyDirectory();

        // level:0
        $logger = new StreamLogger($logfiole = "$directory/log.txt");
        $logger->setPresetPlugins()->debug('message');
        that($logfiole)->fileContainsAll(["DEBUG", "message"]);

        // level:1
        $logger = new StreamLogger($logfiole = "$directory/log.ltsv");
        $logger->setPresetPlugins()->debug('message');
        that($logfiole)->fileContainsAll(["level:DEBUG\tmessage:message"]);

        // level:2
        $logger = new StreamLogger($logfiole = "$directory/log.json");
        $logger->setPresetPlugins()->debug('message');
        that($logfiole)->fileContainsAll(['"level": "DEBUG"', '"message": "message"']);

        // level:3
        $logger = new StreamLogger($logfiole = "$directory/log.yml");
        $logger->setPresetPlugins()->debug('message');
        that($logfiole)->fileContainsAll(['level: DEBUG', 'message: message']);
    }

    function test_supports()
    {
        $directory = $this->emptyDirectory();

        $logger = new StreamLogger("file://$directory/file-log.txt", [
            'flock' => true,
        ]);
        that($logger)->_lock(LOCK_EX)->isTrue();
        that($logger)->_flush()->isTrue();
        $logger->debug('message');
        unset($logger);
        that("$directory/file-log.txt")->fileEquals("message\n");

        $logger = new StreamLogger("file-simple://$directory/simple-log.txt", [
            'flock' => true,
        ]);
        that($logger)->_lock(LOCK_EX)->isNull();
        that($logger)->_flush()->isNull();
        $logger->debug('message');
        unset($logger);
        that("$directory/simple-log.txt")->fileEquals("message\n");

        gc_collect_cycles();
    }

    function test_redis()
    {
        if (!REDIS_URL) {
            $this->markTestSkipped();
        }

        $logfile = REDIS_URL . "/log.txt";

        @unlink($logfile);
        $logger = new StreamLogger($logfile);
        $logger->debug('message1');
        $logger->debug('message2');
        unset($logger);
        that($logfile)->fileEquals("message1\nmessage2\n");
    }

    function test_s3()
    {
        if (!S3_URL) {
            $this->markTestSkipped();
        }

        $logfile = S3_URL . "/log.txt";

        @unlink($logfile);
        $logger = new StreamLogger($logfile);
        $logger->debug('message1');
        $logger->debug('message2');
        unset($logger);
        that($logfile)->fileEquals("message1\nmessage2\n");
    }
}
