<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace ryunosuke\Test\SimpleLogger\FileType;

use ryunosuke\SimpleLogger\FileType\Csv;
use ryunosuke\Test\AbstractTestCase;

class CsvTest extends AbstractTestCase
{
    function test_alignment()
    {
        $type  = new Csv(',', true, 64);
        $lines = [];
        that($lines[] = $type->encode(['a' => '1', 'b' => '2', 'c' => '3']))->is('1,2,3' . "\n");
        that($lines[] = $type->encode(['a' => '11', 'b' => '22', 'c' => '33']))->is('11,22,33' . "\n");
        that($lines[] = $type->encode(['a' => '111', 'b' => '222', 'c' => '333']))->is('111,222,333' . "\n");
        that($lines[] = $type->encode(['a' => '111', 'b' => '222', 'c' => '33']))->is('111,222,33' . "\n");
        that($lines[] = $type->encode(['a' => '111', 'b' => '22', 'c' => '33']))->is('111,22, "33"' . "\n");
        that($lines[] = $type->encode(['a' => '11', 'b' => '22', 'c' => '33']))->is('11, "22",33' . "\n");
        that($lines[] = $type->encode(['a' => '11', 'b' => '22', 'c' => '3']))->is('11, "22",3' . "\n");
        that($lines[] = $type->encode(['a' => '11', 'b' => '2', 'c' => '3']))->is('11, "2", "3"' . "\n");
        that($lines[] = $type->encode(['a' => '1', 'b' => '2', 'c' => '3']))->is('1,  "2", "3"' . "\n");
        that($lines[] = $type->encode(['a' => '1', 'b' => '2', 'c' => '']))->is('1,  "2", ""' . "\n");
        that($lines[] = $type->encode(['a' => '1', 'b' => '', 'c' => '']))->is('1,  "",  ""' . "\n");
        that($lines[] = $type->encode(['a' => '', 'b' => '', 'c' => '']))->is(',   "",  ""' . "\n");
        that(implode("", $lines))->is(<<<CSV
        1,2,3
        11,22,33
        111,222,333
        111,222,33
        111,22, "33"
        11, "22",33
        11, "22",3
        11, "2", "3"
        1,  "2", "3"
        1,  "2", ""
        1,  "",  ""
        ,   "",  ""
        
        CSV,);

        $type  = new Csv(',', true, 64);
        $lines = [];
        that($lines[] = $type->encode(['a' => 'baseA', 'b' => "baseB", 'c' => 'baseC']))->is('baseA,baseB,baseC' . "\n");
        that($lines[] = $type->encode(['a' => '1', 'b' => "line1\nline2line2line2\nline3", 'c' => '3']))->is(<<<CSV
        1,    "line1
        line2line2line2
        line3",     "3"
        
        CSV,);
        that($lines[] = $type->encode(['a' => '1', 'b' => '2', 'c' => '3']))->is('1,    "2",  "3"' . "\n");
        that(implode("", $lines))->is(<<<CSV
        baseA,baseB,baseC
        1,    "line1
        line2line2line2
        line3",     "3"
        1,    "2",  "3"
        
        CSV,);
    }
}
