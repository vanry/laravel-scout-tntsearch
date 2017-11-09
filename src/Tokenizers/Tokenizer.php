<?php

namespace Vanry\Scout\Tokenizers;

use TeamTNT\TNTSearch\Support\TokenizerInterface;

abstract class Tokenizer implements TokenizerInterface
{
    public function tokenize($text)
    {
        return is_numeric($text) ? [] : $this->getTokens($text);
    }

    abstract protected function getTokens($text);
}
