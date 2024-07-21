<?php

use ryunosuke\SimpleLogger\AbstractLogger;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\LoggerTrait;
use ryunosuke\StreamWrapper\Stream\RedisStream;
use ryunosuke\StreamWrapper\Stream\S3Stream;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/ryunosuke/phpunit-extension/inc/bootstrap.php';

\ryunosuke\PHPUnit\Actual::generateStub(__DIR__ . '/../src', __DIR__ . '/.stub', 2);

define('STREAM_WRAPPER_DEBUG', true);

defined('REDIS_URL') or define('REDIS_URL', null);
defined('S3_URL') or define('S3_URL', null);

class FileSimpleStreamWrapper
{
    private $handle;
    public  $context;

    public function stream_open(string $path, string $mode, int $options, &$opened_path): bool
    {
        return !!$this->handle = fopen(preg_replace('#^file-simple://#', 'file://', $path), $mode);
    }

    public function stream_write(string $data): int
    {
        return fwrite($this->handle, $data);
    }

    public function stream_eof(): bool
    {
        return feof($this->handle);
    }

    public function stream_flush(): bool
    {
        throw new RuntimeException();
    }

    public function stream_lock(int $operation): bool
    {
        throw new RuntimeException();
    }
}

stream_wrapper_register('file-simple', FileSimpleStreamWrapper::class);

if (REDIS_URL) {
    RedisStream::register(REDIS_URL);
}
if (S3_URL) {
    S3Stream::register(S3_URL);
}

class ArrayLogger extends AbstractLogger
{
    use LoggerTrait;

    private array $logs;

    public function __construct(&$logs)
    {
        $logs       = [];
        $this->logs = &$logs;
    }

    protected function _write(Log $log): void
    {
        $this->logs[] = $log;
    }
}
