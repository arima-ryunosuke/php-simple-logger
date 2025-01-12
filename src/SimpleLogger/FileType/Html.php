<?php

namespace ryunosuke\SimpleLogger\FileType;

class Html extends AbstractFileType
{
    public function getFlags(): int
    {
        return self::FLAG_ONELINER | self::FLAG_STRUCTURE | self::FLAG_NESTING | self::FLAG_COMPLETION;
    }

    public function head(array $logdata): string
    {
        $styles = [
            'ol{font-family:monospace; padding:0;}',
            'dl{font-family:monospace; display:grid; grid-template-columns:max-content auto;}',
            'dt{font-weight:bold;}',
        ];
        return "<style>" . implode('', $styles) . "</style>\n";
    }

    public function encode(array $logdata): string
    {
        return $this->render($logdata) . "<hr>\n";
    }

    private function render($value): string
    {
        if (!is_array($value)) {
            return $this->text($value);
        }
        elseif ($value === array_values($value)) {
            return $this->olli($value);
        }
        else {
            return $this->dldtdd($value);
        }
    }

    private function text(string $string): string
    {
        return self::replaceBreakLine(htmlspecialchars($string, ENT_QUOTES), "<br>");
    }

    private function dldtdd(array $array): string
    {
        $dl = "<dl>";
        foreach ($array as $key => $value) {
            $dt = "<dt>{$this->text($key)}</dt>";
            $dd = "<dd>{$this->render($value)}</dd>";
            $dl .= $dt . $dd;
        }
        $dl .= "</dl>";

        return $dl;
    }

    private function olli(array $array): string
    {
        $ol = "<ol>";
        foreach ($array as $value) {
            $li = "<li>{$this->render($value)}</li>";
            $ol .= $li;
        }
        $ol .= "</ol>";

        return $ol;
    }
}
