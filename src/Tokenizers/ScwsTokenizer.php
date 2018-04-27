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

        $result = $this->scws->getResult();

        return $result === false ? [] : array_column($result, 'word');
    }

    public function getScws()
    {
        return $this->scws;
    }
}
