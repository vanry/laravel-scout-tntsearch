<?php

namespace Vanry\Scout;

use Latrell\Scws\Scws;
use Illuminate\Support\Manager;
use TeamTNT\TNTSearch\Support\Tokenizer;
use Vanry\Scout\Tokenizers\ScwsTokenizer;
use Vanry\Scout\Tokenizers\JiebaTokenizer;
use Vanry\Scout\Tokenizers\PhpAnalysisTokenizer;

class TokenizerManager extends Manager
{
    /**
     * Create a Jieba tokenizer instance.
     *
     * @return \Vanry\Scout\Tokenizers\JiebaTokenizer
     */
    public function createJiebaDriver()
    {
        return new JiebaTokenizer($this->app['config']['scout.tntsearch.tokenizer.jieba']);
    }

    /**
     * Create a PhpAnalysis tokenizer instance.
     *
     * @return \Vanry\Scout\Tokenizers\PhpAnalysisTokenizer
     */
    public function createAnalysisDriver()
    {
        $analysis = new Phpanalysis;

        foreach ($this->app['config']['scout.tntsearch.tokenizer.analysis'] as $key => $value) {
            $key = camel_case($key);

            if (property_exists($analysis, $key)) {
                $analysis->$key = $value;
            }
        }

        return new PhpAnalysisTokenizer($analysis);
    }

    /**
     * Create a Scws tokenizer instance.
     *
     * @return \Vanry\Scout\Tokenizers\ScwsTokenizer
     */
    public function createScwsDriver()
    {
        return new ScwsTokenizer(
            new Scws($this->app['config']['scout.tntsearch.tokenizer.scws'])
        );
    }

    /**
     * Create a default tokenizer instance.
     *
     * @return \TeamTNT\TNTSearch\Support\Tokenizer
     */
    public function createDefaultDriver()
    {
        return new Tokenizer;
    }

    /**
     * Get the default session driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['scout.tntsearch.tokenizer.driver'];
    }
}
