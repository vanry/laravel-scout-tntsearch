<?php

namespace Vanry\Scout\Tokenizers;

use Latrell\Scws\Scws;

class ScwsTokenizer extends Tokenizer
{
    protected $scws;

    public function __construct(Scws $scws)
    {
        $this->scws = $scws;
    }

    public function getTokens($text)
    {
        $this->scws->sendText($text);

        $tokens = [];

        while ($result = $this->scws->getResult()) {
            $tokens = array_merge($tokens, array_column($result, 'word'));
        }

        return $tokens;
    }

    public function getScws()
    {
        return $this->scws;
    }
}
