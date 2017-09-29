<?php

namespace Vanry\Scout\Tokenizers;

use TeamTNT\TNTSearch\Support\TokenizerInterface;

abstract class Tokenizer implements TokenizerInterface
{
    protected $scws;

    public function __construct(array $config = [])
    {
        $this->scws = new Scws($config);
    }

    public function tokenize($text)
    {
        return is_numeric($text) ? [] : $this->getTokens($text);
    }

    abstract protected function getTokens($text);
}
