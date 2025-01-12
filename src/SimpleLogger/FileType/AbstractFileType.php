<?php

namespace ryunosuke\SimpleLogger\FileType;

abstract class AbstractFileType
{
    public const FLAG_PLAIN      = 1 << 0; // not structure
    public const FLAG_ONELINER   = 1 << 1; // single line
    public const FLAG_DATATYPE   = 1 << 2; // can have nest
    public const FLAG_STRUCTURE  = 1 << 3; // structure
    public const FLAG_NESTING    = 1 << 4; // can contain nest
    public const FLAG_COMPLETION = 1 << 5; // completion as 1 document

    public static function createByExtension(string $extension): self
    {
        switch (strtolower($extension)) {
            default:
            case "log":
                return new Text(false);
            case "txt":
                return new Text(true);
            case "html":
                return new Html();
            case "csv":
                return new Csv(",", false);
            case "tsv":
                return new Csv("\t", false);
            case "ltsv":
                return new Ltsv();
            case "json":
                return new Json();
            case "jsonl":
            case "ndjson":
            case "ldjson":
                return new JsonLine();
            case "yaml":
            case "yml":
                return new Yaml();
            case "ndyaml":
            case "ndyml":
            case "ldyaml":
            case "ldyml":
                return new YamlLine();
        }
    }

    public static function replaceBreakLine(string $string, string $replacement): string
    {
        return str_replace(["\r\n", "\r", "\n"], $replacement, $string);
    }

    abstract public function getFlags(): int;

    public function head(array $logdata): string
    {
        return '';
    }

    abstract public function encode(array $logdata): string;
}
