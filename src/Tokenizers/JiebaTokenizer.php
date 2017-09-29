<?php

namespace Vanry\Scout\Tokenizers;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;

class JiebaTokenizer extends Tokenizer
{
    public function __construct(array $options = [])
    {
        Jieba::init($options);
        Finalseg::init($options);
    }

    public function getTokens($text)
    {
        return Jieba::cutForSearch($text);
    }
}
