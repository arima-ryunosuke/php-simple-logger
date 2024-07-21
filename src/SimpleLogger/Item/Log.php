<?php

namespace ryunosuke\SimpleLogger\Item;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use stdClass;

class Log extends stdClass
{
    protected const LOG_LEVELS = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    protected const LOG_LABELS = [
        0 => LogLevel::EMERGENCY,
        1 => LogLevel::ALERT,
        2 => LogLevel::CRITICAL,
        3 => LogLevel::ERROR,
        4 => LogLevel::WARNING,
        5 => LogLevel::NOTICE,
        6 => LogLevel::INFO,
        7 => LogLevel::DEBUG,
    ];

    public int|string $level;

    public mixed $message;

    public array $context;

    private array $order;

    private bool $levelUnset;

    private bool $filterConsumption;

    public function __construct(int|string $level, mixed $message, array $context)
    {
        $this->level             = $level;
        $this->message           = $message;
        $this->context           = $context;
        $this->order             = [];
        $this->levelUnset        = false;
        $this->filterConsumption = false;
    }

    public function setOrder(array $order): self
    {
        $this->order = array_combine($order, $order);
        return $this;
    }

    public function setLevelUnset(bool $unset): self
    {
        $this->levelUnset = $unset;
        return $this;
    }

    public function setFilterConsumption(bool $filterConsumption): self
    {
        $this->filterConsumption = $filterConsumption;
        return $this;
    }

    public function interpolate(): self
    {
        if (is_string($this->message)) {
            $placeholder = $this->filterConsumption ? new stdClass() : null;
            $main        = function (array $context, array $parents) use (&$main, $placeholder) {
                foreach ($context as $key => $value) {
                    if (preg_match('#\A[a-zA-Z0-9_.]+\z#u', $key)) {
                        $keys   = $parents;
                        $keys[] = $key;

                        if (is_array($value)) {
                            $main($value, $keys);
                            continue;
                        }

                        if (($string = self::stringifyOrNull($value)) !== null) {
                            $this->message = str_replace("{" . implode('.', $keys) . "}", $string, $this->message, $count);
                            if ($count && $placeholder) {
                                $context = &$this->context;
                                foreach ($keys as $key) {
                                    $context = &$context[$key];
                                }
                                $context = $placeholder;
                            }
                        }
                    }
                }
            };

            // level is specially treated as part of context
            $level = $this->levelUnset ? [] : ['level' => $this->level];
            $main($this->context + $level, []);

            // filter consumption empty
            $array_filter_recursive = function ($array, $callback) use (&$array_filter_recursive): array {
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        $array[$k] = $array_filter_recursive($v, $callback);
                        if (!$array[$k]) {
                            unset($array[$k]);
                        }
                    }
                    else {
                        if (!$callback($v)) {
                            unset($array[$k]);
                        }
                    }
                }
                return $array;
            };
            $this->context          = $array_filter_recursive($this->context, fn($v) => $v !== $placeholder);
        }

        return $this;
    }

    public function arrayize(bool $stringableOnly, bool $convertString): array
    {
        $entries = array_merge([
            'level'   => $this->level,
            'message' => $this->message,
        ], $this->context);

        if ($this->levelUnset) {
            unset($entries['level']);
        }

        foreach ($entries as $key => $value) {
            $strv = self::stringifyOrNull($value);

            if ($stringableOnly && $strv === null) {
                unset($entries[$key]);
            }

            if ($convertString && $strv !== null) {
                $entries[$key] = $strv;
            }
        }

        return self::arrayPickup($entries, $this->order) + $entries;
    }

    // <editor-fold desc="Utils">

    public static function levelAsInt($logLevel): int
    {
        // digit is unconditional for custom log level
        if (ctype_digit("{$logLevel}")) {
            return (int) $logLevel;
        }

        return static::LOG_LEVELS[strtolower($logLevel)] ?? throw new InvalidArgumentException("loglevel '{$logLevel}' is not defined.");
    }

    public static function levelAsString($logLevel): string
    {
        // string is unconditional for custom log level
        if (!ctype_digit("{$logLevel}")) {
            return (string) $logLevel;
        }

        return static::LOG_LABELS[$logLevel] ?? throw new InvalidArgumentException("loglevel '{$logLevel}' is not defined.");
    }

    public static function stringifyOrNull($value): ?string
    {
        if (is_array($value)) {
            return null;
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return null;
        }

        if (is_null($value) || is_bool($value)) {
            return var_export($value, true);
        }

        return (string) $value;
    }

    public static function arrayPickup(array $array, array $keymap): array
    {
        $result = [];

        foreach ($keymap as $old => $new) {
            if (array_key_exists($old, $array)) {
                $result[$new] = $array[$old];
            }
        }

        return $result;
    }

    // </editor-fold>
}
