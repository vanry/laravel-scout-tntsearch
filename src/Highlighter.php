<?php

namespace Vanry\Scout;

use TeamTNT\TNTSearch\Support\Tokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class Highlighter
{
    protected $tokenizer;

    public function __construct(TokenizerInterface $tokenizer = null)
    {
        $this->tokenizer = $tokenizer ?: new Tokenizer;
    }

    public function highlight($text, $query, $tag = 'em')
    {
        $terms = $this->tokenizer->tokenize($query);

        $patterns = array_map(function ($term) {
            return "/{$term}/is";
        }, array_unique($terms));

        $replacement = "<{$tag}>\$0</{$tag}>";

        return preg_replace($patterns, $replacement, $text);
    }

    public function getTokenizer()
    {
        return $this->tokenizer;
    }
}
