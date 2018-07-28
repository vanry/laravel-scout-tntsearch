
## 安装

通过 `composer` 安装:

``` bash
composer require vanry/laravel-scout-tntsearch
```

添加到服务提供者:

```php
// config/app.php
'providers' => [
    // ...
    Laravel\Scout\ScoutServiceProvider::class,
    Vanry\Scout\TNTSearchScoutServiceProvider::class,
],
```

在 `.env` 文件中添加 `SCOUT_DRIVER=tntsearch`

然后就可以将 `scout.php` 配置文件发布到 `config` 目录。

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

在 `config/scout.php` 中添加:

```php

'tntsearch' => [
    'storage' => storage_path('indexes'), //必须有可写权限
    'fuzziness' => env('TNTSEARCH_FUZZINESS', false),
    'searchBoolean' => env('TNTSEARCH_BOOLEAN', false),
    'asYouType' => false,

    'fuzzy' => [
        'prefix_length' => 2,
        'max_expansions' => 50,
        'distance' => 2,
    ],

    'tokenizer' => [
        'driver' => env('TNTSEARCH_TOKENIZER', 'default'),

        'jieba' => [
            'dict' => 'small',
            //'user_dict' => resource_path('dicts/mydict.txt'), //自定义词典路径
        ],

        'analysis' => [
            'result_type' => 2,
            'unit_word' => true,
            'differ_max' => true,
        ],

        'scws' => [
            'charset' => 'utf-8',
            'dict' => '/usr/local/scws/etc/dict.utf8.xdb',
            'rule' => '/usr/local/scws/etc/rules.utf8.ini',
            'multi' => 1,
            'ignore' => true,
            'duality' => false,
        ],
    ],

    'stopwords' => [
        '的',
        '了',
        '而是',
    ],
],

```

你也可以在模型中直接添加 `asYouType` 选项, 参考下面的示例。

## 用法


```php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    public $asYouType = true;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...

        return $array;
    }
}
```

同步数据到搜索服务:

`php artisan scout:import App\\Post`


使用模型进行搜索:

`Post::search('世界杯直播')->get();`

## 中文分词

目前支持 `jieba`, `phpanalysis` 和 `scws` 中文分词，在 `.env` 文件中配置 `TNTSEARCH_TOKENIZER` 可选值 为 `jieba`, `analysis`, `scws`, `default`， 其中 `default` 为 `TNTSearch` 自带的分词器。

> 考虑到性能问题，建议生产环境使用由`C`语言编写的`scws`分词扩展。

- 使用 `jieba` 分词器，需安装 `fukuball/jieba-php`

        composer require fukuball/jieba-php

- 使用 `phpanalysis` 分词器，需安装 `lmz/phpanalysis`

        composer require lmz/phpanalysis

- 使用 `scws` 分词器，需安装 `vanry/scws`

        composer require vanry/scws

分别在 `config/scout.php` 中的 `jieba`, `analysis` 和 `scws` 中修改配置。

## 高亮

在 `view composer` 中引入 `highlighter`

```php
use Vanry\Scout\Highlighter;

// ...

view()->composer('search', function ($view) {
    $tokenizer = app('tntsearch.tokenizer')->driver();

    $view->with('highlighter', new Highlighter($tokenizer));
});
```

```php
// search.blade.php

{!! $highlighter->highlight($article->title, $query) !!}

{!! $highlighter->highlight($article->excerpt, $query) !!}
```

> 默认使用 `em` 作为高亮标签，在 `css` 中设置样式即可。

## 教程

[laravel下TNTSearch+jieba-php实现全文搜索](https://baijunyao.com/article/154)
