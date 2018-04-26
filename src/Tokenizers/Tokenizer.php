<?php

namespace Vanry\Scout\Tokenizers;

use TeamTNT\TNTSearch\Support\TokenizerInterface;

abstract class Tokenizer implements TokenizerInterface
{
    public function tokenize($text, $stopwords = [])
    {
        $tokens = is_numeric($text) ? [] : $this->getTokens($text);

        return array_diff($tokens, $stopwords);
    }

    abstract protected function getTokens($text);
}
