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

    /** @var int|string */
    public $level;

    /** @var string|\Stringable|mixed */
    public $message;

    public array $context;

    private array $order;

    public function __construct($level, $message, array $context)
    {
        $this->level   = $level;
        $this->message = $message;
        $this->context = $context;
        $this->order   = [];
    }

    public function setOrder(array $order): self
    {
        $this->order = array_combine($order, $order);
        return $this;
    }

    public function interpolate(): self
    {
        if (is_string($this->message)) {
            $main = function (array $context, array $parents) use (&$main) {
                foreach ($context as $key => $value) {
                    if (preg_match('#\A[a-zA-Z0-9_.]+\z#u', $key)) {
                        $keys   = $parents;
                        $keys[] = $key;

                        if (is_array($value)) {
                            $main($value, $keys);
                            continue;
                        }

                        if (($string = self::stringifyOrNull($value)) !== null) {
                            $this->message = str_replace("{" . implode('.', $keys) . "}", $string, $this->message);
                        }
                    }
                }
            };

            // level is specially treated as part of context
            $main($this->context + ['level' => $this->level], []);
        }

        return $this;
    }

    public function arrayize(bool $stringableOnly, bool $convertString): array
    {
        $entries = array_merge([
            'level'   => $this->level,
            'message' => $this->message,
        ], $this->context);

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

        $throw = function ($e) { throw $e; }; // under php8.0
        return static::LOG_LEVELS[strtolower($logLevel)] ?? $throw(new InvalidArgumentException("loglevel '{$logLevel}' is not defined."));
    }

    public static function levelAsString($logLevel): string
    {
        // string is unconditional for custom log level
        if (!ctype_digit("{$logLevel}")) {
            return (string) $logLevel;
        }

        $throw = function ($e) { throw $e; }; // under php8.0
        return static::LOG_LABELS[$logLevel] ?? $throw(new InvalidArgumentException("loglevel '{$logLevel}' is not defined."));
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
