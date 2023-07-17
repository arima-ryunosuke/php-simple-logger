<?php

namespace ryunosuke\Test\SimpleLogger\Plugins;

use ArrayLogger;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins\LocationAppendPlugin;
use ryunosuke\Test\AbstractTestCase;

class LocationAppendPluginTest extends AbstractTestCase
{
    function test_apply()
    {
        $wrapper = new class() {
            public function noStop($logger, $message)
            {
                $logger->error($line = __LINE__);
                return $line;
            }

            public function delegate($logger, $level, $message)
            {
                $logger->log($level, $message);
            }

            public function nest($logger, $message)
            {
                $this->nest1($logger, $message);
            }

            private function nest1($logger, $message)
            {
                $this->nest2($logger, $message);
            }

            private function nest2($logger, $message)
            {
                $this->nest3($logger, $message);
            }

            private function nest3($logger, $message)
            {
                $logger->debug($message);
            }
        };

        $logger = new ArrayLogger($logs);
        $logger->appendPlugin(new LocationAppendPlugin(['line' => 'lineNo', 'function' => 'method'], [
            get_class($wrapper) . "::delegate",
            get_class($wrapper) . "::nest",
        ]));

        $lines   = [];
        $lines[] = $wrapper->noStop($logger, null);
        $wrapper->delegate($logger, 'info', $lines[] = __LINE__);
        $wrapper->nest($logger, $lines[] = __LINE__);
        $logger->log('info', $lines[] = __LINE__);
        $logger->debug($lines[] = __LINE__);

        that(array_column(array_map(fn(Log $log) => $log->arrayize(false, false), $logs), 'lineNo'))->is($lines);
        that(array_column(array_map(fn(Log $log) => $log->arrayize(false, false), $logs), 'method'))->is([
            'noStop',
            __FUNCTION__,
            __FUNCTION__,
            __FUNCTION__,
            __FUNCTION__,
        ]);
    }
}
