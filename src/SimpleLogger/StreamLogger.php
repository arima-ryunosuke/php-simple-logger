<?php

namespace ryunosuke\SimpleLogger;

use Closure;
use DateTime;
use Exception;
use ryunosuke\SimpleLogger\FileType\AbstractFileType;
use ryunosuke\SimpleLogger\Item\Log;

class StreamLogger extends AbstractLogger
{
    use LoggerTrait;

    /** @var resource */
    private       $handle;
    private array $metadata;
    /*readonly*/
    public AbstractFileType $filetype;
    private array           $options;

    private float $lastRotationTime;
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
            'suffix'   => null,
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

        // normalize suffix
        if ($options['suffix'] !== null) {
            if (!$options['suffix'] instanceof Closure) {
                $options['suffix'] = fn() => date($options['suffix']);
            }
        }

        $logfile        = $this->_appendSuffix($filename, $options['suffix']);
        $this->handle   = fopen($logfile, $options['mode'], false, $context);
        $this->metadata = stream_get_meta_data($this->handle) + ['filename' => $filename, 'first' => true];
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

    public function reopen(?string $newfile = null): void
    {
        $newfile ??= $this->metadata['uri'];

        $this->_flush();
        $this->_close();

        $this->handle   = fopen($newfile, $this->metadata['mode'], false, $this->metadata['wrapper_data']->context ?? null);
        $this->metadata = stream_get_meta_data($this->handle) + ['first' => true] + $this->metadata; // keep filename
    }

    public function rotate(): bool
    {
        // don't have to do it every time, do it sometimes
        $this->lastRotationTime ??= microtime(true);

        if ((microtime(true) - $this->lastRotationTime) >= 2) {
            $this->lastRotationTime = microtime(true);

            $logfile = $this->_appendSuffix($this->metadata['filename'], $this->options['suffix']);
            if ($logfile !== $this->metadata['uri']) {
                $this->reopen($logfile);

                return true;
            }
        }

        return false;
    }

    public function setPresetPlugins(): static
    {
        // common before
        $plugins = [
            'LevelAuto'       => new Plugins\LevelAutoPlugin(),
            'LevelFromPhp'    => new Plugins\LevelFromPhpPlugin(),
            'LevelNormalize'  => new Plugins\LevelNormalizePlugin(true),
            'TimeAppend'      => new Plugins\ContextAppendPlugin([
                'time' => fn() => (new DateTime())->format(DateTime::RFC3339_EXTENDED),
            ]),
            'MessageCallback' => new Plugins\MessageCallbackPlugin(),
        ];

        // by filetype
        $fileflags = $this->filetype->getFlags();

        if ($fileflags & AbstractFileType::FLAG_PLAIN) {
            $plugins = array_merge($plugins, [
                'ThrowableMangle'  => new Plugins\ThrowableManglePlugin(true, 'chains', /* for future scope (ini_get('zend.exception_ignore_args') ? 0 : 1024) */),
                'MessageStringify' => new Plugins\MessageStringifyPlugin(),
                'MessageRewrite'   => new Plugins\MessageRewritePlugin("[{time}] {level}: %s"),
            ]);
        }

        if ($fileflags & AbstractFileType::FLAG_NESTING) {
            $plugins = array_merge($plugins, [
                'ThrowableMangle' => new Plugins\ThrowableManglePlugin(false, 'traces'),
            ]);
        }
        else {
            $plugins = array_merge($plugins, [
                'ThrowableMangle' => new Plugins\ThrowableManglePlugin(false, ''),
            ]);
        }

        if ($fileflags & AbstractFileType::FLAG_STRUCTURE) {
            $plugins = array_merge($plugins, [
                'ContextOrder' => new Plugins\ContextOrderPlugin(['time', 'level', 'message']),
            ]);
        }

        $this->setPlugins($plugins);

        return $this;
    }

    protected function _write(Log $log): void
    {
        $fileflags = $this->filetype->getFlags();

        $logarray = $log->arrayize(!($fileflags & AbstractFileType::FLAG_NESTING), !($fileflags & AbstractFileType::FLAG_DATATYPE));
        $logtext  = $this->filetype->encode($logarray);

        $this->rotate();

        $this->_lock(LOCK_EX);
        if ($this->metadata['first']) {
            $this->metadata['first'] = false;
            if (($this->_fstat()['size'] ?? null) === 0) {
                fwrite($this->handle, $this->filetype->head($logarray));
            }
        }
        fwrite($this->handle, $logtext);
        $this->_lock(LOCK_UN);

        $this->_flush();
    }

    protected function _fstat(): ?array
    {
        $this->methodCache[__METHOD__] ??= $this->_supportsByMetadata('stream_stat');
        if ($this->methodCache[__METHOD__]) {
            try {
                return fstat($this->handle);
            }
            catch (Exception) {
                $this->methodCache[__METHOD__] = false;
            }
        }
        return null;
    }

    protected function _lock(int $operation): ?bool
    {
        if ($this->options['flock']) {
            $this->methodCache[__METHOD__] ??= $this->_supportsByMetadata('stream_lock');
            if ($this->methodCache[__METHOD__]) {
                try {
                    return flock($this->handle, $operation);
                }
                catch (Exception) {
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
                catch (Exception) {
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

    private function _appendSuffix(string $filename, ?Closure $suffixer): string
    {
        if ($suffixer !== null) {
            $pathinfo = pathinfo($filename);
            $filename = "{$pathinfo['dirname']}/{$pathinfo['filename']}{$suffixer()}.{$pathinfo['extension']}";
        }
        return $filename;
    }
}
