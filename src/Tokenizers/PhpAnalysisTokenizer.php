<?php

namespace Vanry\Scout\Tokenizers;

use Phpanalysis\Phpanalysis;

class PhpAnalysisTokenizer extends Tokenizer
{
    protected $analysis;

    public function __construct()
    {
        $this->analysis = new Phpanalysis;

        foreach ($this->getConfig('analysis') as $key => $value) {
            $key = camel_case($key);

            if (property_exists($this->analysis, $key)) {
                $this->analysis->$key = $value;
            }
        }
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
