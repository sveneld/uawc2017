<?php

namespace uawc\Parser;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BrovaryradagovuaParser implements LinkParserInterface
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

        $this->purifier->config->set('AutoFormat.AutoParagraph', true);
        $this->purifier->config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
        $this->purifier->config->set('Output.TidyFormat', true);
        $this->purifier->config->set('Attr.AllowedClasses', []);
        $this->purifier->config->set('CSS.AllowedProperties', []);
        $this->purifier->config->set('AutoFormat.RemoveEmpty', true);
        $this->purifier->config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
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
        $documentContent = $htmlContent->find('div.otstupVertVneshn', 0)->find('div', 1);
        $cleanContent = $this->purifier->purify($documentContent->innertext);
        $cleanContent = str_replace('><', ">\n<", $cleanContent);
        $cleanContent = strip_tags($cleanContent);
        $cleanContent = preg_replace('/[\n]{2,}/', '', $cleanContent);
        $cleanContent = str_replace(['версія для друку', 'Перейти до всіх'], '', $cleanContent);
        $cleanContent = trim($cleanContent);

        return $cleanContent;
    }
}