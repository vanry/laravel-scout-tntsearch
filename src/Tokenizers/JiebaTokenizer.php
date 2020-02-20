<?php

namespace Vanry\Scout\Tokenizers;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;

class JiebaTokenizer extends Tokenizer
{
    public function __construct()
    {
        $config = $this->getConfig('jieba');

        Jieba::init($config);

        if (isset($config['user_dict'])) {
            Jieba::loadUserDict($config['user_dict']);
        }

        Finalseg::init($config);
    }

    public function getTokens($text)
    {
        return Jieba::cutForSearch($text);
    }
}
