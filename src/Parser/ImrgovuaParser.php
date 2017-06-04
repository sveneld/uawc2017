<?php

namespace uawc\Parser;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ImrgovuaParser implements LinkParserInterface
{
    /**
     * @var Client
     */
    private $curlClient;
    /**
     * @var \simple_html_dom
     */
    private $parser;
    /**
     * @var \HTMLPurifier
     */
    private $purifier;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $curlClient,
        \simple_html_dom $parser,
        \HTMLPurifier $purifier,
        LoggerInterface $logger
    ) {
        $this->curlClient = $curlClient;
        $this->parser = $parser;
        $this->purifier = $purifier;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function parse($url)
    {
        $result = $this->curlClient->get($url);
        if ($result->getStatusCode() != 200) {
            return self::PARSE_RESULT_NOT_FOUND;
        }

        $htmlContent = $this->parser->load($result->getBody()->getContents());
        $cleanContent = $htmlContent->find('div[itemprop="articleBody"]', 0);
        $cleanContent = $cleanContent->innertext;

        return $cleanContent;
    }
}