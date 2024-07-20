simple logger
====

## Description

psr-3 を実装したシンプルなロガーパッケージです。
ストリームのみの実装ですが、php には強力な StreamWrapper があるので、（Wrapper があれば）実質的にあらゆる対象に書き込むことができます。

出力形式は拡張子で判断されます。

## Install

```json
{
    "require": {
        "ryunosuke/simple-logger": "dev-master"
    }
}
```

## Feature

（Wrapper があれば）あらゆる個所に出力できます。
例えば下記は S3 にログを出力します。

```
$s3client = new \Aws\S3\S3Client([
    'credentials' => [
        'key'    => 'foo',
        'secret' => 'bar',
    ],
    'region'      => 'ap-northeast-1',
    'version'     => 'latest',
]);
\Aws\S3\StreamWrapper::register($s3client);

$logger = new \ryunosuke\SimpleLogger\StreamLogger('s3://bucket-name/log.txt');
$logger->debug('debug message');
$logger->alert('alert message');
```

ただし、実際のところ（個人の観測範囲内では） file でスキームで書くことが最も多く、個人的にはログというものはファイルログ+fluentd さえあれば十分事足りると考えています（餅は餅屋に任せたい）。
StreamLogger は「与えられたパスが file:// である」という前提を置いていないだけ、という話です。

StreamLogger の他に ChainLogger というものがあります。
これはいわゆるコンポジットなロガーで、保持しているすべてのロガーにログを展開します。

つまり下記のようなことが可能です。
通常は notice 以上のログが `path/to/log.jsonl` に書き込まれますが、 error 以上は smtp スキームを通じてメール送信も行われます。

```
$logger = new \ryunosuke\SimpleLogger\ChainLogger([
    (new \ryunosuke\SimpleLogger\StreamLogger('file://path/to/log.jsonl'))->setPresetPlugins()->prependPlugin(new \ryunosuke\SimpleLogger\Plugins\LevelFilterPlugin('notice')),
    (new \ryunosuke\SimpleLogger\StreamLogger('smtp://hoge@example.com/log.txt'))->setPresetPlugins()->prependPlugin(new \ryunosuke\SimpleLogger\Plugins\LevelFilterPlugin('error')),
]);
$logger->notice('notice message');
$logger->alert('alert message');
```

URL の機能面は下記のようになります。

```
scheme://hostname/path/to/logfile.json
───   ──── ────        ── 
  │        │       │            └─  出力形式です（monolog でいう Formatter に相当します）
  │        │       └────────  ストリームラッパーによって様々です（例えば file ではディレクトリです）
  │        └───────────── ストリームラッパーによって様々です。不必要なこともあります（例えば file では不要ですが、S3 ならバケット名になります）
  └────────────────── 出力先です（monolog でいう Handler に相当します）
```

monolog における Processer は Plugin 機構を用いています。
ログレベルによるフィルタや日時文字列の追加などはすべて Plugin として実装されます。
ロガーとしてはそれらの情報を一切特別扱いしません。

### Plugins

上記の通り、StreamLogger はストリームへの抽象的な書き込みを担うだけでログの内容には一切の口出しをしていません。
出力形式は拡張子で制御しますが、出力内容については Plugin を設定することで制御します。

- AbstractPlugin
    - プラグインの抽象クラスです
    - 最低限 apply メソッドを実装する必要があります
    - apply はログに何らかの変更を加えます。null を返すとそのログはスキップされます（LevelFilterPlugin が好例です）
- ClosurePlugin
    - 指定したクロージャがコールバックされます
    - アドホックなプラグインを作成するためには無名クラスで `new class() extends AbstractPlugin{...}` する必要がありますがそれを嫌ってクロージャだけで作成できるようにしたものです
- ContextAppendPlugin
    - 任意のエントリをコンテキストに加えます
    - 典型的には日時です。他にホスト名やプロセスIDなどもログとしては有用でしょう
- ContextConsumePlugin
    - `message {context.key}` のような値の埋め込みに使用されたコンテキストを伏せます
    - コンテキストは得てして「メッセージに埋め込みたい」と「ログの参考情報として」の2つ状況があります。前者はメッセージに埋め込まれるため、コンテキストとしては不要です
    - そういった場合に伏せられるようになります。出力形式が構造化テキストでないと指定に意味はありません
- ContextFilterPlugin
    - 任意のコンテキストを変換・削除します
    - ものによっては超絶でかいオブジェクトが context に渡ってきたりするため、切り詰めたり削除したりできます
- ContextOrderPlugin
    - コンテキストの出力順を指定します
    - 例えば「行番号・…雑多なもの…・ファイル名」だったらログが見にくくて仕方ないでしょう。また大抵のログは「日時・ログレベル」が冒頭に来ている方が有用です
    - そういった並び順を指定します。出力形式が構造化テキストでないと指定に意味はありません
- LevelFilterPlugin
    - ログレベルでのフィルタです。ロガーであれば実質的に必須でしょう
    - 「NOTICE 以上」のような指定だけではなく、「NOTICE 以上 ERROR 以下」のような指定も可能です
- LevelNormalizePlugin
    - ログレベルを文字列化します
    - psr3 はログレベルの型を規約していないため、渡ってくるレベルは int だったり大文字だったり小文字だったりします。それを文字列に統一します
- LocationAppendPlugin
    - ログメソッドの呼び出し場所（ファイル・行番号など）をコンテキストに加えます
- MessageCallbackPlugin
    - ログメッセージに callable が来た場合にコールバックされます
    - `$logger->debug(function () {...})` などとすればログ生成のコスト自体を抑えることができます
- MessageStringifyPlugin
    - ログメッセージに非 stringable が来た場合に文字列化します
    - `$logger->debug([1, 2, 3])` のように非 stringable でもログられるようにできます
- MessageRewritePlugin
    - ログメッセージを書き換えます
- SuppressPlugin
    - 指定した秒数の間、同一ログの出力を抑制します
    - 基本的には DEBUG レベルで用います
- ThrowableManglePlugin
    - 例外オブジェクトをよしなにハンドリングします
    - ログメッセージ自体が Throwable であるとか {exception} コンテキストがあるかなどでなんとかして Throwable を見やすく整形します

## Note

出力は StreamWrapper の実装に強く依存します。これは特徴でもありますがデメリットでもあります。
例えば fopen の a フラグと相性の悪いミドルウェアも存在するため、全ログで flush するとか各ログで逐次 write するとか、細かな制御はこのパッケージ側では制御できません。
細かな制御のためには専用の StreamWrapper を書く必要があります。

## License

MIT

## FAQ

- Q. なんで車輪の再開発した？
  - A. 元々 monolog を好んで使っていたんですが、少し仰々しく感じてきて、実際のところ logger は StreamHandler しか使わないし見通し良くなるように自前実装したかったのです
- Q. いや、monolog なら Redis とか Slack とか有用なのもあるよ？
  - A. 専用の handler を書かずとも php には既に StreamWrapper という強力な抽象化レイヤーが存在します。handler で使い分けるよりも `s3://hoge/log.txt` とか `redis://hoge/log.txt` とか書くだけでよしなに判断してくれる方が好みなのです
- Q. それにしたって自前実装しなくても…
  - A. monolog の吐き出すログが好みではない、というのも多分にありました。シンプルに json ログを吐きたいだけなのに多少なりとも設定が必要で、ほぼ設定レスなロガーが欲しかったのです

## Release

バージョニングは romantic versioning に準拠します（semantic versioning ではありません）。

- メジャー: 大規模な互換性破壊の際にアップします（アーキテクチャ、クラス構造の変更など）
- マイナー: 小規模な互換性破壊の際にアップします（引数の変更、タイプヒントの追加など）
- パッチ: 互換性破壊はありません（デフォルト引数の追加や、新たなクラスの追加、コードフォーマットなど）

### 1.1.3

- [feature] 同一ログを一定時間抑制するプラグイン
- [feature] context 値を埋め込んだらその context を伏せるプラグイン
- [feature] プラグインに名前を付けて後からソートしやすいように変更

### 1.1.2

- [fixbug] ThrowableManglePlugin の不具合修正

### 1.1.1

- [feature] プラグインの置き換え機能
- [feature] 例外オブジェクトの引数に対応

### 1.1.0

- [change] php>=8.0

### 1.0.1

- [feature] オートローテーション機能を追加

### 1.0.0

- 公開
