<?php

namespace uawc\Parser;

interface LinkParserInterface
{
    const PARSE_RESULT_NOT_FOUND = 'notFound';

    /**
     * @param string $url
     * @return string
     */
    public function parse($url);
}