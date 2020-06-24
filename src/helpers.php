<?php

use Vanry\Scout\Highlighter;

if (! function_exists('highlight')) {
    /**
     * Highlight search results.
     *
     * @param  string $text
     * @param  string $query
     * @param  string $tag
     * @return string
     */
    function highlight($text, $query, $tag = 'em')
    {
        return app(Highlighter::class)->highlight($text, $query, $tag);
    }
}
