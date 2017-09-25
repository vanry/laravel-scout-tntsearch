<?php

return [

    'tntsearch' => [
        'storage' => storage_path('index'),
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
    ],
];
