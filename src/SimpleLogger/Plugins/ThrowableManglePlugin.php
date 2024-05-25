<?php

namespace ryunosuke\SimpleLogger\Plugins;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ryunosuke\SimpleLogger\Item\Log;
use Throwable;

class ThrowableManglePlugin extends AbstractPlugin
{
    private bool   $asString;
    private string $chainKey;
    private int    $argLimit;

    public function __construct(bool $asString, string $chainKey = 'chains', int $argLimit = 0)
    {
        $this->asString = $asString;
        $this->chainKey = $chainKey;
        $this->argLimit = $argLimit;
    }

    public function apply(Log $log): ?Log
    {
        // direct throwable message
        if ($log->message instanceof Throwable) {
            $log->context = $this->convert($log->message) + $log->context;
        }

        // context throwable
        if (($log->context['exception'] ?? null) instanceof Throwable) {
            $log->context = $this->convert($log->context['exception']) + $log->context;
        }

        return $log;
    }

    private function convert(Throwable &$throwable): array
    {
        $result = [];
        if ($this->asString) {
            if ($this->argLimit) {
                $throwable = $this->stringifyThrowable($throwable);
            }
            else {
                // file(line) -> file:line
                $throwable = preg_replace('@^(#\d+ )(.+?)\((\d+)\)@m', '$1$2:$3', (string) $throwable);
            }
        }
        else {
            if (strlen($this->chainKey)) {
                // do reverse because string cast same too
                $result[$this->chainKey] = array_reverse($this->mangleThrowable($throwable));
            }

            $throwable = $throwable->getMessage();
        }
        return $result;
    }

    private function stringifyThrowable(Throwable $throwable): string
    {
        $all = [];
        for ($current = $throwable; $current; $current = $current->getPrevious()) {
            $all[] = $current;
        }

        $stacktrace = [];
        foreach (array_reverse($all) as $i => $t) {
            $lines   = [];
            $lines[] = vsprintf('%s: %s in %s:%d', [
                get_class($t),
                $t->getMessage(),
                $t->getFile(),
                $t->getLine(),
            ]);
            $lines[] = "Stack trace:";
            foreach ($t->getTrace() as $n => $trace) {
                $lines[] = vsprintf('#%d %s:%s %s(%s)', [
                    $n,
                    $trace['file'],
                    $trace['line'],
                    (isset($trace['class']) ? $trace['class'] . $trace['type'] : '') . $trace['function'],
                    $this->argumentsMangler($this->reflectTraceFunction($trace), $trace['args'] ?? [])->toString($this->argLimit),
                ]);
            }
            $stacktrace[] = ($i === 0 ? "" : "\nNext: ") . implode("\n", $lines);
        }

        return implode("\n", $stacktrace);
    }

    private function mangleThrowable(Throwable $throwable): array
    {
        return array_merge([
            [
                'class'   => get_class($throwable),
                'message' => $throwable->getMessage(),
                'code'    => $throwable->getCode(),
                'trace'   => array_merge([
                    [
                        'file'     => $throwable->getFile(),
                        'line'     => $throwable->getLine(),
                        'function' => '__construct',
                        'class'    => get_class($throwable),
                        'type'     => '::',
                    ],
                ], array_map(function ($trace) {
                    if ($this->argLimit) {
                        $trace['args'] = $this->argumentsMangler($this->reflectTraceFunction($trace), $trace['args'] ?? [])->toArray($this->argLimit);
                    }
                    else {
                        unset($trace['args']);
                    }
                    return $trace;
                }, $throwable->getTrace())),
            ],
        ], $throwable->getPrevious() ? $this->mangleThrowable($throwable->getPrevious()) : []);
    }

    private function reflectTraceFunction(array $trace): ?ReflectionFunctionAbstract
    {
        try {
            if (isset($trace['class'], $trace['function'])) {
                return new ReflectionMethod($trace['class'], $trace['function']);
            }
            else {
                return new ReflectionFunction($trace['function']);
            }
        }
        catch (Throwable) {
            return null;
        }
    }

    private function argumentsMangler(?ReflectionFunctionAbstract $reffunc, array $arguments): object
    {
        return new class ($reffunc?->getParameters() ?? [], $arguments) {
            public function __construct(private array $parameters, private array $arguments) { }

            public function toString(int $maxlength): string
            {
                $n         = 0;
                $length    = 0;
                $arguments = [];
                foreach ($this->arguments as $key => $argument) {
                    /** @var ReflectionParameter $parameter */
                    $parameter = $this->parameters[$key] ?? null;
                    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                    if ($parameter?->getAttributes(\SensitiveParameter::class)[0] ?? null) {
                        $string = 'Object(SensitiveParameterValue)';
                    }
                    else {
                        $string = match (gettype($argument)) {
                            default                         => var_export($argument, true),
                            'string'                        => var_export(mb_strimwidth($argument, 0, 1024, '...'), true),
                            'NULL'                          => 'null',
                            'object'                        => sprintf('Object(%s)', get_debug_type($argument)),
                            'resource', 'resource (closed)' => (string) $argument,
                            'array'                         => '[' . (new self([], $argument))->toString($maxlength - $length) . ']',
                        };
                    }

                    if ($length > $maxlength) {
                        $arguments[] = '... more ' . (count($this->arguments) - $n);
                        break;
                    }
                    else {
                        $arguments[] = (($key === $n) ? "" : var_export($key, true) . " => ") . $string;
                    }

                    $length += strlen($string);
                    $n++;
                }
                return implode(', ', $arguments);
            }

            public function toArray(int $maxsize): array
            {
                $n         = 0;
                $size      = 0;
                $arguments = [];
                foreach ($this->arguments as $key => $argument) {
                    /** @var ReflectionParameter $parameter */
                    $parameter = $this->parameters[$key] ?? null;
                    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                    if ($parameter?->getAttributes(\SensitiveParameter::class)[0] ?? null) {
                        $string = 'Object(SensitiveParameterValue)';
                    }
                    else {
                        $string = match (gettype($argument)) {
                            default                         => $argument,
                            'string'                        => mb_strimwidth($argument, 0, 1024, '...'),
                            'NULL'                          => null,
                            'object'                        => sprintf('Object(%s)', get_debug_type($argument)),
                            'resource', 'resource (closed)' => (string) $argument,
                            'array'                         => (new self([], $argument))->toArray($maxsize - $size),
                        };
                    }

                    if ($size > $maxsize) {
                        $arguments[] = '... more ' . (count($this->arguments) - $n);
                        break;
                    }
                    else {
                        $arguments[] = $string;
                    }

                    $size++;
                    $n++;
                }
                return $arguments;
            }
        };
    }
}
