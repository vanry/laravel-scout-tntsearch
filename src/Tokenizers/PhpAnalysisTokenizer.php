<?php

namespace Vanry\Scout\Tokenizers;

use Phpanalysis\Phpanalysis;

class PhpAnalysisTokenizer extends Tokenizer
{
    protected $analysis;

    public function __construct(Phpanalysis $analysis)
    {
        $this->analysis = $analysis;
    }

    public function getTokens($text)
    {
        $this->analysis->SetSource($text);

        $this->analysis->StartAnalysis();

        $result = $this->analysis->GetFinallyResult();

        $result = str_replace(['(', ')'], '', trim($result));

        return explode(' ', $result);
    }

    public function getAnalysis()
    {
        return $this->analysis;
    }
}
