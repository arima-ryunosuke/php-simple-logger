<?php

namespace ryunosuke\SimpleLogger;

use Psr\Log\LoggerInterface;
use ryunosuke\SimpleLogger\Item\Log;
use ryunosuke\SimpleLogger\Plugins;

abstract class AbstractLogger implements LoggerInterface
{
    /** @var Plugins\AbstractPlugin[] */
    private array $plugins = [];

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function setPlugins(array $plugins): self
    {
        $this->plugins = $plugins;
        return $this;
    }

    public function prependPlugin(Plugins\AbstractPlugin ...$plugins): self
    {
        return $this->setPlugins(array_merge($plugins, $this->plugins));
    }

    public function appendPlugin(Plugins\AbstractPlugin ...$plugins): self
    {
        return $this->setPlugins(array_merge($this->plugins, $plugins));
    }

    public function log($level, $message, array $context = []): void
    {
        $log = new Log($level, $message, $context);

        foreach ($this->plugins as $plugin) {
            $log = $plugin->apply($log);
            if ($log === null) {
                return;
            }
        }

        $this->_write($log->interpolate());
    }

    abstract protected function _write(Log $log): void;
}
