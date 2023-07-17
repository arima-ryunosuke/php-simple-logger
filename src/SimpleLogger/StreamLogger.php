<?php

namespace ryunosuke\SimpleLogger;

use DateTime;
use Exception;
use ryunosuke\SimpleLogger\FileType\AbstractFileType;
use ryunosuke\SimpleLogger\Item\Log;

class StreamLogger extends AbstractLogger
{
    use LoggerTrait;

    /** @var resource */
    private                  $handle;
    private array            $metadata;
    private AbstractFileType $filetype;
    private array            $options;

    private array $methodCache = [];

    public function __construct(string $filename, array $options = [])
    {
        // normalize scheme (no use default scheme)
        $filename = strtr($filename, [DIRECTORY_SEPARATOR => '/']);
        if (!preg_match('#^[a-z][-+.0-9a-z]*://#', $filename)) {
            $filename = "file://$filename";
        }

        // default options
        $options = array_replace([
            'mode'     => 'ab',
            'context'  => null,
            'flock'    => false,
            'flush'    => true,
            'filetype' => fn($extension) => AbstractFileType::createByExtension($extension),
        ], $options);

        // ready context
        $context = $options['context'];
        if (is_array($context)) {
            $scheme  = parse_url($filename, PHP_URL_SCHEME);
            $context = stream_context_create([
                $scheme => $context,
            ]);
        }

        $this->handle   = fopen($filename, $options['mode'], false, $context);
        $this->metadata = stream_get_meta_data($this->handle);
        $this->filetype = $options['filetype'](pathinfo($filename, PATHINFO_EXTENSION));
        $this->options  = $options;
    }

    public function __destruct()
    {
        $this->_flush();
        $this->_close();

        try {
            unset($this->handle);
        }
        catch (Exception $e) {
        }
    }

    public function reopen(): void
    {
        $this->_flush();
        $this->_close();

        $this->handle   = fopen($this->metadata['uri'], $this->metadata['mode'], false, $this->metadata['wrapper_data']->context ?? null);
        $this->metadata = stream_get_meta_data($this->handle);
    }

    public function setPresetPlugins(): self
    {
        // common
        $this->appendPlugin(
            new Plugins\LevelNormalizePlugin(true),
            new Plugins\ContextAppendPlugin([
                'time' => fn() => (new DateTime())->format(DateTime::RFC3339_EXTENDED),
            ]),
            new Plugins\MessageCallbackPlugin(),
        );

        // by filetype
        $fileflags = $this->filetype->getFlags();

        if ($fileflags & AbstractFileType::FLAG_PLAIN) {
            $this->appendPlugin(
                new Plugins\ThrowableManglePlugin(true),
                new Plugins\MessageStringifyPlugin(),
                new Plugins\MessageRewritePlugin("[{time}] {level}: %s"),
            );
        }

        if ($fileflags & AbstractFileType::FLAG_NESTING) {
            $this->appendPlugin(
                new Plugins\ThrowableManglePlugin(false, 'traces'),
            );
        }
        else {
            $this->appendPlugin(
                new Plugins\ThrowableManglePlugin(false, ''),
            );
        }

        if ($fileflags & AbstractFileType::FLAG_STRUCTURE) {
            $this->appendPlugin(
                new Plugins\ContextOrderPlugin(['time', 'level', 'message']),
            );
        }

        return $this;
    }

    protected function _write(Log $log): void
    {
        $fileflags = $this->filetype->getFlags();

        $logarray = $log->arrayize(!($fileflags & AbstractFileType::FLAG_NESTING), !($fileflags & AbstractFileType::FLAG_DATATYPE));
        $logtext  = $this->filetype->encode($logarray);

        $this->_lock(LOCK_EX);
        fwrite($this->handle, $logtext);
        $this->_lock(LOCK_UN);

        $this->_flush();
    }

    protected function _lock(int $operation): ?bool
    {
        if ($this->options['flock']) {
            $this->methodCache[__METHOD__] ??= $this->_supportsByMetadata('stream_lock');
            if ($this->methodCache[__METHOD__]) {
                try {
                    return flock($this->handle, $operation);
                }
                catch (Exception $e) {
                    $this->methodCache[__METHOD__] = false;
                }
            }
        }
        return null;
    }

    protected function _flush(): ?bool
    {
        if ($this->options['flush']) {
            $this->methodCache[__METHOD__] ??= $this->_supportsByMetadata('stream_flush');
            if ($this->methodCache[__METHOD__]) {
                try {
                    return fflush($this->handle);
                }
                catch (Exception $e) {
                    $this->methodCache[__METHOD__] = false;
                }
            }
        }
        return null;
    }

    protected function _close(): ?bool
    {
        $this->methodCache[__METHOD__] ??= $this->_supportsByMetadata('stream_close');
        if ($this->methodCache[__METHOD__]) {
            return fclose($this->handle);
        }
        return null;
    }

    private function _supportsByMetadata(string $methodName): bool
    {
        if (is_object($this->metadata['wrapper_data'] ?? null)) {
            if (method_exists($this->metadata['wrapper_data'], $methodName)) {
                // future scope: treat never type as not implemented.
                // @codeCoverageIgnoreStart
                if (version_compare(PHP_VERSION, 8.1) >= 0) {
                    $refmethod = new \ReflectionMethod($this->metadata['wrapper_data'], $methodName);
                    if ($rtype = $refmethod->getReturnType()) {
                        if ($rtype instanceof \ReflectionNamedType) {
                            if ($rtype->isBuiltin() && $rtype->getName() === 'never') {
                                return false;
                            }
                        }
                    }
                }
                // @codeCoverageIgnoreEnd
                return true;
            }
            return false;
        }

        return $this->metadata['stream_type'] === 'STDIO';
    }
}
