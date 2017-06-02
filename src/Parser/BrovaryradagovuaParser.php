<?php
namespace uawc\Parser;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BrovaryradagovuaParser implements LinkParserInterface
{
    /**
     * @var \simple_html_dom
     */
    private $parser;
    /**
     * @var Client
     */
    private $curlClient;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $links = [];

    public function __construct(
        Client $curlClient,
        \simple_html_dom $parser,
        LoggerInterface $logger
    ) {
        $this->curlClient = $curlClient;
        $this->parser = $parser;
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
        $content = $htmlContent->find('div.otstupVertVneshn',0)->find('div', 1);
        $printLink = $content->find('.js-print', 0);
        $content = trim(str_replace(['версія для друку', 'Перейти до всіх', '&nbsp;'], '', $content->plaintext));
        $content = wordwrap($content);
        print_r($content);
        die();

        return $content;
    }
}