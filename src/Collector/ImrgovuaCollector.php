<?php

namespace uawc\Collector;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ImrgovuaCollector implements LinkCollectorInterface
{
    const DOMAIN = 'https://imr.gov.ua';
    const PAGE_WITH_LINKS = 'https://imr.gov.ua/rozporyadzhennya-miskogo-golovi';
    const PAGE_PARSE_COUNT = 10;

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
    public function collect()
    {
        $requests = function ($total) {
            $uri = self::PAGE_WITH_LINKS . '?start=';
            for ($i = 0; $i < $total; $i++) {
                yield function () use ($uri, $i) {
                    return $this->curlClient->getAsync($uri . ($i * 10));
                };
            }
        };

        $pool = new Pool($this->curlClient, $requests(self::PAGE_PARSE_COUNT), [
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $index) {
                $html = $this->parser->load($response->getBody()->getContents());
                $links = $html->find('a');
                foreach ($links as $link) {
                    if (preg_match('/rozporyadzhennya-miskogo-golovi\/[\d]{1,}.*/', $link->getAttribute('href'))) {
                        $this->links[] = self::DOMAIN.$link->getAttribute('href');
                    }
                }
                unset($html);
            },
            'rejected' => function ($reason, $index) {
                $this->logger->log(LogLevel::ERROR, 'Some Error during collecting links', compact('reason', 'index'));
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $this->links;
    }
}