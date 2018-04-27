<?php

return [

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
];
