<?php

return [

    'storage' => storage_path('indexes'), // 必须有可写权限

    'fuzziness' => env('TNTSEARCH_FUZZINESS', false),

    'searchBoolean' => env('TNTSEARCH_BOOLEAN', false),

    'asYouType' => false,

    'fuzzy' => [
        'prefix_length' => 2,
        'max_expansions' => 50,
        'distance' => 2,
    ],

    'default' => env('TNTSEARCH_TOKENIZER', 'tntsearch'),

    'tokenizers' => [
        'tntsearch' => [
            'driver' => TeamTNT\TNTSearch\Support\Tokenizer::class,
        ],

        'analysis' => [
            'driver' => Vanry\Scout\Tokenizers\PhpAnalysisTokenizer::class,
            'result_type' => 2,
            'to_lower' => true,
            'unit_word' => true,
            'differ_max' => true,
        ],

        'jieba' => [
            'driver' => Vanry\Scout\Tokenizers\JiebaTokenizer::class,
            'dict' => 'small',
            //'user_dict' => resource_path('dicts/mydict.txt'), //自定义词典路径
        ],

        'scws' => [
            'driver' => Vanry\Scout\Tokenizers\ScwsTokenizer::class,
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
];
