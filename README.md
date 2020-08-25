
> 说明: `2.x` 版本只支持 `Laravel 5.5` 以上版本，`Laravel 5.5`以下版本请使用 [1.x版本](https://github.com/vanry/laravel-scout-tntsearch/tree/1.x)。

## 安装

> 需安装并开启 `sqlite` 扩展

``` bash
composer require vanry/laravel-scout-tntsearch
```

### Laravel

* 发布 `scout` 配置文件，已安装 `scout` 可省略。

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

* 如需修改 `tntsearch` 默认配置，发布 `tntsearch` 配置文件。

```bash
php artisan vendor:publish --provider="Vanry\Scout\TNTSearchScoutServiceProvider"
```

### Lumen

`Lumen` 需将服务提供者添加到 `bootstrap/app.php`

```php
// bootstrap/app.php

// 取消注释
$app->withFacades();
$app->withEloquent()

// 注意先后顺序
$app->register(Vanry\Scout\LumenServiceProvider::class);
$app->register(Laravel\Scout\ScoutServiceProvider::class);
```

在根目录中创建 `config` 文件夹， 将 `laravel scout` 配置文件 `scout.php` 复制到 `config` 中。

如需修改 `tntsearch` 默认配置，则将配置文件 `tntsearch.php` 复制 `config` 中进行修改。

### 启用

在 `.env` 文件中添加

```bash
SCOUT_DRIVER=tntsearch
```

## 用法

1. 模型添加 `Searchable Trait`

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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => strip_tags($this->body),
        ];
    }
}
```

2. 导入模型 创建索引

```php
# scout 命令
php artisan scout:import 'App\Post'

# tntsearch 命令, 性能更好
php artisan tntsearch:import 'App\Post'
```

3. 使用索引进行搜索

```php
Post::search('laravel教程')->get();
```


## 中文分词

目前支持 `scws`, `jieba` 和 `phpanalysis` 中文分词，默认使用 `phpanalysis` 分词。

### 对比

* `scws` 是用 `C` 语言编写的 `php` 扩展，性能最好，分词效果好，但不支持 `Windows` 系统。
* `jieba` 为 `python` 版本结巴分词的 `php` 实现，分词效果最好，尤其是新词发现，不足之处是性能较差，占用内存大。
* `phpanalysis` 是 `php` 编写的一款轻量分词器，分词效果不错，性能介于 `scws` 和 `jieba` 两者之间。

### 安装

使用 `scws` 或者 `jieba`，需安装对应的分词驱动。

- **scws**

```bash
composer require vanry/scws
```

- **jieba**

```bash
composer require fukuball/jieba-php
```

在 `.env` 文件中配置

```bash
# scws
TNTSEARCH_TOKENIZER=scws

# jieba
TNTSEARCH_TOKENIZER=jieba
```

### 问题

使用 `jieba` 分词可能会出现内存分配不足的错误信息:

> PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes)

在代码中增加内存限制即可

```php
ini_set('memory_limit', '1024M');
```

## 高亮

默认使用 `em` 作为高亮 `html` 标签，在 `css` 中设置高亮样式即可，也可以自定义高亮标签。

* **@highlight 指令**

```
@highlight($text, $query, $tag);
```
> * $text: 要高亮的字段
> * $query: 搜索词
> * $tag: 高亮的 html 标签

```php
// 高亮 title 字段
@highlight($post->title, $query);

// 用 strong 作为高亮标签
@highlight($post->title, $query, 'strong');
```

* **highlight 帮助函数**

```php
highlight($post->title, $query);

highlight($post->title, $query, 'strong');
```

* **Highlighter 对象**

```php
use Vanry\Scout\Highlighter;

// ...

app(Highlighter::class)->highlight($post->title, $query);
```

> `highlight` 帮助函数和 `Highlighter` 对象适合在 `api` 等非 `html` 视图中使用。
