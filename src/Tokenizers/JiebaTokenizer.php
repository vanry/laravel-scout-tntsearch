<?php

namespace Vanry\Scout\Tokenizers;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class JiebaTokenizer implements TokenizerInterface
{
    public function __construct(array $options = [])
    {
        Jieba::init($options);
        Finalseg::init($options);
    }

    public function tokenize($text)
    {
        return Jieba::cutForSearch($text);
    }
}
