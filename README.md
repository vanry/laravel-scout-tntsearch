
> 说明: `2.x` 版本只支持 `Laravel 5.5` 以上版本，`Laravel 5.5`以下版本请使用 [1.x版本](https://github.com/vanry/laravel-scout-tntsearch/tree/1.x)。

## 安装

通过 `composer` 安装

``` bash
composer require vanry/laravel-scout-tntsearch
```

### Laravel

`Laravel` 具有包自动发现功能，不用手动添加服务提供者。

发布 `scout` 配置文件

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

如果要修改 `tntsearch` 默认配置，也可发布 `tntsearch` 配置文件

```bash
php artisan vendor:publish --provider="Vanry\Scout\TNTSearchScoutServiceProvider"
```

### Lumen

`Lumen` 需将服务提供者添加到 `bootstrap/app.php`

```php
// bootstrap/app.php

// 取消注释
$app->withEloquent()

// 注意先后顺序
$app->register(Vanry\Scout\LumenServiceProvider::class);
$app->register(Laravel\Scout\ScoutServiceProvider::class);
```

### 启用

在 `.env` 文件中添加

```bash
SCOUT_DRIVER=tntsearch
```


## 用法

* 添加 `Searchable Trait` 到模型

```php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

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

* 同步数据到搜索索引

```php
# scout 命令
php artisan scout:import App\\Post

# tntsearch 命令, 性能更好
php artisan tntsearch:import App\\Post
```


* 使用模型进行搜索

```php
Post::search('laravel教程')->get();
```

## 中文分词

目前支持 `scws`, `jieba` 和 `phpanalysis` 中文分词。

### 对比

* `scws` 是用 `C` 语言编写的 `php` 扩展，性能最佳，分词效果好，但不支持 `Windows` 系统。
* `jieba` 为 `python` 版本结巴分词的 `php` 实现，分词效果最好，尤其是新词发现，不足之处是性能较差，占用内存大。
* `phpanalysis` 是 `php` 编写的一款轻量分词器，分词效果不错，性能介于 `scws` 和 `jieba` 两者之间。

### 安装

- **scws**

```bash
composer require vanry/scws
```

- **jieba**

```bash
composer require fukuball/jieba-php
```

- **phpanalysis**

```bash
composer require lmz/phpanalysis
```

### 配置

在 `.env` 文件中配置

```bash
TNTSEARCH_TOKENIZER=scws
```

> 可选值为 `default`, `scws`, `jieba`, `phpanalysis`, 其中 `default` 为 `TNTSearch` 自带的分词器。

### 问题

使用 `jieba` 分词可能会出现内存分配不足的错误信息:

> PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes)

在代码中增加内存限制即可

```php
ini_set('memory_limit', '1024M');
```

## 高亮

在 `view composer` 中引入 `highlighter`, 可使用以上任一分词器。

```php
use Vanry\Scout\Highlighter;
use Vanry\Scout\Tokenizers\PhpAnalysisTokenizer;

// ...

view()->composer('search', function ($view) {
    $view->with('highlighter', new Highlighter(new PhpAnalysisTokenizer));
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
