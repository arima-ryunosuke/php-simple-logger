<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace ryunosuke\Test\SimpleLogger\FileType;

use ryunosuke\SimpleLogger\FileType\AbstractFileType;
use ryunosuke\SimpleLogger\FileType\Csv;
use ryunosuke\SimpleLogger\FileType\Html;
use ryunosuke\SimpleLogger\FileType\Json;
use ryunosuke\SimpleLogger\FileType\JsonLine;
use ryunosuke\SimpleLogger\FileType\Ltsv;
use ryunosuke\SimpleLogger\FileType\Text;
use ryunosuke\SimpleLogger\FileType\Yaml;
use ryunosuke\SimpleLogger\FileType\YamlLine;
use ryunosuke\Test\AbstractTestCase;

class AbstractFileTypeTest extends AbstractTestCase
{
    function test_createByExtension()
    {
        that(AbstractFileType::class)::createByExtension('hoge')->isInstanceOf(Text::class);
        that(AbstractFileType::class)::createByExtension('txt')->isInstanceOf(Text::class);
        that(AbstractFileType::class)::createByExtension('html')->isInstanceOf(Html::class);
        that(AbstractFileType::class)::createByExtension('csv')->isInstanceOf(Csv::class);
        that(AbstractFileType::class)::createByExtension('tsv')->isInstanceOf(Csv::class);
        that(AbstractFileType::class)::createByExtension('ltsv')->isInstanceOf(Ltsv::class);
        that(AbstractFileType::class)::createByExtension('json')->isInstanceOf(Json::class);
        that(AbstractFileType::class)::createByExtension('jsonl')->isInstanceOf(JsonLine::class);
        that(AbstractFileType::class)::createByExtension('yaml')->isInstanceOf(Yaml::class);
        that(AbstractFileType::class)::createByExtension('ldyml')->isInstanceOf(YamlLine::class);
    }

    function test_replaceBreakLine()
    {
        that(AbstractFileType::class)::replaceBreakLine("\r\n\n\r", 'br')->is('brbrbr');
    }

    function test_all()
    {
        $data = ['level' => 99, 'message' => 'hoge'];
        $nest = [
            'nest' => [
                ['id' => 1, 'name' => 'hoge'],
                ['id' => 2, 'name' => 'fuga'],
            ],
        ];

        $filetype = AbstractFileType::createByExtension('txt');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_PLAIN);
        that($filetype)->encode($data)->is("hoge\n");

        $filetype = AbstractFileType::createByExtension('html');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_ONELINER | AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_NESTING | AbstractFileType::FLAG_COMPLETION);
        that($filetype)->head($data)->is("<style>ol{font-family:monospace; padding:0;}dl{font-family:monospace; display:grid; grid-template-columns:max-content auto;}dt{font-weight:bold;}</style>\n");
        that($filetype)->encode($data + $nest)->is("<dl><dt>level</dt><dd>99</dd><dt>message</dt><dd>hoge</dd><dt>nest</dt><dd><ol><li><dl><dt>id</dt><dd>1</dd><dt>name</dt><dd>hoge</dd></dl></li><li><dl><dt>id</dt><dd>2</dd><dt>name</dt><dd>fuga</dd></dl></li></ol></dd></dl><hr>\n");

        $filetype             = AbstractFileType::createByExtension('csv');
        $filetype->withHeader = true;
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_COMPLETION);
        that($filetype)->head($data)->is("level,message\n");
        that($filetype)->encode($data)->is("99,hoge\n");

        $filetype             = AbstractFileType::createByExtension('tsv');
        $filetype->withHeader = false;
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_COMPLETION);
        that($filetype)->head($data)->is("");
        that($filetype)->encode($data)->is("99\thoge\n");

        $filetype = AbstractFileType::createByExtension('ltsv');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_ONELINER | AbstractFileType::FLAG_STRUCTURE);
        that($filetype)->encode($data)->is("level:99\tmessage:hoge\n");

        $filetype = AbstractFileType::createByExtension('json');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_DATATYPE | AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_NESTING);
        that($filetype)->encode($data + $nest)->is(<<<EXPECTED
        {
            "level": 99,
            "message": "hoge",
            "nest": [
                {
                    "id": 1,
                    "name": "hoge"
                },
                {
                    "id": 2,
                    "name": "fuga"
                }
            ]
        }
        
        EXPECTED,);

        $filetype = AbstractFileType::createByExtension('jsonl');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_ONELINER | AbstractFileType::FLAG_DATATYPE | AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_NESTING);
        that($filetype)->encode($data + $nest)->is('{"level":99,"message":"hoge","nest":[{"id":1,"name":"hoge"},{"id":2,"name":"fuga"}]}' . "\n");

        $filetype = AbstractFileType::createByExtension('yaml');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_DATATYPE | AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_NESTING | AbstractFileType::FLAG_COMPLETION);
        that($filetype)->encode($data + $nest)->is(<<<EXPECTED
        ---
        level: 99
        message: hoge
        nest:
            -
                id: 1
                name: hoge
            -
                id: 2
                name: fuga
        
        EXPECTED,);

        $filetype = AbstractFileType::createByExtension('ldyml');
        that($filetype)->getFlags()->is(AbstractFileType::FLAG_ONELINER | AbstractFileType::FLAG_DATATYPE | AbstractFileType::FLAG_STRUCTURE | AbstractFileType::FLAG_NESTING | AbstractFileType::FLAG_COMPLETION);
        that($filetype)->encode($data + $nest)->is("{ level: 99, message: hoge, nest: [{ id: 1, name: hoge }, { id: 2, name: fuga }] }\n");
    }
}
