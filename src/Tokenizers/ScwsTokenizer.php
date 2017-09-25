<?php

namespace Vanry\Scout\Tokenizers;

use Latrell\Scws\Scws;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class ScwsTokenizer implements TokenizerInterface
{
    protected $scws;

    public function __construct(array $config = [])
    {
        $this->scws = new Scws($config);
    }

    public function tokenize($text)
    {
        $this->scws->sendText($text);

        return array_column($this->scws->getResult(), 'word');
    }
}
