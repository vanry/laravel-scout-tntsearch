<?php

return [

    'default' => env('TNTSEARCH_TOKENIZER', 'phpanalysis'),

    'storage' => storage_path('indices'),

    'stemmer' => TeamTNT\TNTSearch\Stemmer\NoStemmer::class,

    'tokenizers' => [
        'phpanalysis' => [
            'driver' => Vanry\Scout\Tokenizers\PhpAnalysisTokenizer::class,
            'to_lower' => true,
            'unit_word' => true,
            'differ_max' => true,
            'result_type' => 2,
        ],

        'jieba' => [
            'driver' => Vanry\Scout\Tokenizers\JiebaTokenizer::class,
            'dict' => 'small',
            //'user_dict' => resource_path('dicts/mydict.txt'),
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
