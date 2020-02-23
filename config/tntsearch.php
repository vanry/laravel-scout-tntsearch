<?php

return [

    'default' => env('TNTSEARCH_TOKENIZER', 'tntsearch'),

    'storage' => storage_path('indices'),

    'stemmer' => TeamTNT\TNTSearch\Stemmer\NoStemmer::class,

    'tokenizers' => [
        'tntsearch' => [
            'driver' => TeamTNT\TNTSearch\Support\Tokenizer::class,
        ],

        'jieba' => [
            'driver' => Vanry\Scout\Tokenizers\JiebaTokenizer::class,
            'dict' => 'small',
            //'user_dict' => resource_path('dicts/mydict.txt'),
        ],

        'phpanalysis' => [
            'driver' => Vanry\Scout\Tokenizers\PhpAnalysisTokenizer::class,
            'to_lower' => true,
            'unit_word' => true,
            'differ_max' => true,
            'result_type' => 2,
        ],

        'scws' => [
            'driver' => Vanry\Scout\Tokenizers\ScwsTokenizer::class,
            'multi' => 1,
            'ignore' => true,
            'duality' => false,
            'charset' => 'utf-8',
            'dict' => '/usr/local/scws/etc/dict.utf8.xdb',
            'rule' => '/usr/local/scws/etc/rules.utf8.ini',
        ],
    ],

    'stopwords' => [
        //
    ],

];
