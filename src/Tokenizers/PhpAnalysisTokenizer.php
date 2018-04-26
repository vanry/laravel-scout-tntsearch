<?php

namespace Vanry\Scout\Tokenizers;

use Phpanalysis\Phpanalysis;

class PhpAnalysisTokenizer extends Tokenizer
{
    protected $analysis;

    protected $optimize;

    public function __construct(array $config = [])
    {
        $this->analysis = new Phpanalysis;

        foreach ($config as $key => $value) {
            $key = camel_case($key);

            if (property_exists($this->analysis, $key)) {
                $this->analysis->$key = $value;
            }
        }

        $this->optimize = isset($config['optimize']) ? $config['optimize'] : true;
    }

    public function getTokens($text)
    {
        $this->analysis->SetSource($text);

        $this->analysis->StartAnalysis($this->optimize);

        $result = $this->analysis->GetFinallyResult();

        $result = str_replace(['(', ')'], '', trim($result));

        return explode(' ', $result);
    }
}
