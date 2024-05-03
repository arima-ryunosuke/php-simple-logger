<?php

namespace ryunosuke\SimpleLogger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ChainLogger implements LoggerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    /** @var LoggerInterface[] */
    private array $loggers;

    public function __construct(array $loggers = [])
    {
        $this->loggers = $loggers;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->loggers = ['default' => $logger];
    }

    public function appendLogger(LoggerInterface $logger, string $channel = null): static
    {
        if ($channel === null) {
            $this->loggers[] = $logger;
        }
        else {
            $this->loggers[$channel] = $logger;
        }

        return $this;
    }

    public function removeLogger(string $channel): static
    {
        unset($this->loggers[$channel]);

        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $channel => $logger) {
            $logger->log($level, $message, $context + ['channel' => $channel]);
        }
    }
}
