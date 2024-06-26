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
            'suffix'  => '-His',              // for converage
        ]);
        that($logger)->metadata['mode']->is('a');
        that($logger)->metadata['uri']->is(strtr("file://$directory/file-log" . date('-His') . ".txt", ['\\' => '/']));
        that($logger)->metadata['filename']->is(strtr("file://$directory/file-log.txt", ['\\' => '/']));
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

    function test_rotate()
    {
        // /path/to/log/file => /path//to//log//.//..//file
        $parts     = preg_split('#[/\\\\]#', $this->emptyDirectory());
        $parts[]   = '/./';
        $parts[]   = '/../';
        $directory = implode('/' . DIRECTORY_SEPARATOR, $parts);

        $seq    = 0;
        $logger = new StreamLogger("$directory/file-log.txt", [
            'suffix' => function () use (&$seq) { return $seq; },
        ]);
        that($logger)->rotate()->is(false);
        that($logger)->rotate()->is(false);
        $seq++;
        that($logger)->rotate()->is(true);
        that($logger)->rotate()->is(false);
        that($logger)->rotate()->is(false);
    }

    function test_autorotate()
    {
        $directory = $this->emptyDirectory();

        $seq    = 0;
        $logger = new StreamLogger("file://$directory/file-log.txt", [
            'suffix' => function () use (&$seq) { return "-" . ($seq++ % 2); },
        ]);
        $logger->debug('1');
        $logger->info('2');
        $logger->notice('3');
        that("file://$directory/file-log.txt")->fileNotExists();
        that("file://$directory/file-log-0.txt")->fileEquals("2\n"); // not contain "1"
        that("file://$directory/file-log-1.txt")->fileEquals("3\n");
        that("file://$directory/file-log-2.txt")->fileNotExists();
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

    function test_appendSuffix()
    {
        $directory = $this->emptyDirectory();
        $filename  = "file://$directory/file-log.txt";

        $logger = new StreamLogger($filename);
        that($logger)->_appendSuffix($filename, null)->is($filename);
        that($logger)->_appendSuffix($filename, fn() => '-suffix')->is("file://$directory/file-log-suffix.txt");
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

        // rotate
        $seq    = 0;
        $logger = new StreamLogger($logfile, [
            'suffix' => function () use (&$seq) { return "-" . ($seq++ % 2); },
        ]);
        $logger->debug('message1');
        $logger->debug('message2');
        $logger->debug('message3');
        unset($logger);
        that(REDIS_URL . "/log-0.txt")->fileEquals("message2\n"); // not contain "message1"
        that(REDIS_URL . "/log-1.txt")->fileEquals("message3\n");
        that(REDIS_URL . "/log-2.txt")->fileNotExists();
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

        // rotate
        $seq    = 0;
        $logger = new StreamLogger($logfile, [
            'suffix' => function () use (&$seq) { return "-" . ($seq++ % 2); },
        ]);
        $logger->debug('message1');
        $logger->debug('message2');
        $logger->debug('message3');
        unset($logger);
        that(S3_URL . "/log-0.txt")->fileEquals("message2\n"); // not contain "message1"
        that(S3_URL . "/log-1.txt")->fileEquals("message3\n");
        that(S3_URL . "/log-2.txt")->fileNotExists();
    }
}
