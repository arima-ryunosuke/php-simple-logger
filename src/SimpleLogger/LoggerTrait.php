<?php

namespace ryunosuke\SimpleLogger;

use Psr\Log\LogLevel;

trait LoggerTrait
{
    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @inheritdoc
     * @param mixed $message
     */
    abstract public function log($level, $message, array $context = []);
}
