<?php

namespace ryunosuke\Test\SimpleLogger;

use ryunosuke\SimpleLogger\ChainLogger;
use ryunosuke\SimpleLogger\Plugins\LevelFilterPlugin;
use ryunosuke\SimpleLogger\StreamLogger;
use ryunosuke\Test\AbstractTestCase;

class ChainLoggerTest extends AbstractTestCase
{
    function test_all()
    {
        $directory = $this->emptyDirectory();

        $logger = new ChainLogger();
        $logger->setLogger(new StreamLogger($all_log = "$directory/all.log"));
        $logger->appendLogger((new StreamLogger($debug_log = "$directory/debug.log"))->appendPlugin(new LevelFilterPlugin(['debug'])));
        $logger->appendLogger((new StreamLogger($error_log = "$directory/error.log"))->appendPlugin(new LevelFilterPlugin(['error'])), 'error');
        $logger->appendLogger((new StreamLogger($alert_log = "$directory/alert.log"))->appendPlugin(new LevelFilterPlugin(['alert'])), 'alert');

        $logger->debug('debug');
        $logger->info('info');
        $logger->notice('notice');
        $logger->warning('warning');
        $logger->error('error');
        $logger->critical('critical');
        $logger->alert('alert');
        $logger->emergency('emergency');

        that($all_log)->fileEquals(<<<LOG
        debug
        info
        notice
        warning
        error
        critical
        alert
        emergency
        
        LOG,);
        that($debug_log)->fileEquals("debug\n");
        that($error_log)->fileEquals("error\n");
        that($alert_log)->fileEquals("alert\n");

        $logger->removeLogger('default');
        $logger->removeLogger('error');
        $logger->removeLogger('alert');

        $logger->debug('debug');
        $logger->info('info');
        $logger->notice('notice');
        $logger->warning('warning');
        $logger->error('error');
        $logger->critical('critical');
        $logger->alert('alert');
        $logger->emergency('emergency');

        that($debug_log)->fileEquals("debug\ndebug\n");
        that($error_log)->fileEquals("error\n");
        that($alert_log)->fileEquals("alert\n");
    }
}
