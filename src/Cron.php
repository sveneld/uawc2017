<?php

namespace uawc\SiteMonitoring;

use go\DB\DB;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class Cron
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
     * @var DB
     */
    private $db;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LinkCollectorInterface[]
     */
    private $collectors = [];
    /**
     * @var LinkParserInterface[]
     */
    private $parsers = [];

    public function __construct(
        \simple_html_dom $parser,
        Client $curlClient,
        DB $db,
        LoggerInterface $logger
    ) {
        $this->parser = $parser;
        $this->curlClient = $curlClient;
        $this->db = $db;
        $this->logger = $logger;
    }

    public function collectLinks()
    {
        $sites = $this->db->query('SELECT * FROM sites WHERE `lastUpdateDate` > NOW() - INTERVAL 1 DAY LIMIT 10')->assoc();

        foreach ($sites as $site) {
            $linkInfo = parse_url($site['link']);
            $className = ucfirst(preg_replace('/[^\w]|www/', '', strtolower($linkInfo['host']))) . 'Collector';

            $linkCollector = $this->createCollectorClass($className);
            $links = $linkCollector->collect();

            $insertData = [];
            $addDate = new \DateTimeImmutable();
            $updateAt = $addDate->add(new \DateInterval('PT5M'));
            foreach ($links as $link) {
                $insertData[] = [
                    md5($link), //id
                    $link, //link
                    $addDate->format('Y-m-d H:i:s'), //adDdate
                    $updateAt->format('Y-m-d H:i:s'), //updateAt
                ];
            }
            $this->db->query(
                'INSERT IGNORE INTO siteLinks (`id`, `link`, `adDdate`, `updateAt`) VALUES ?v',
                [$insertData]
            );

            $this->db->query(
                'UPDATE sites set `lastUpdateDate` = NOW() WHERE `id` = ?string:id',
                ['id' => $site['id']]
            );
        }
    }

    public function parseLinks()
    {
        $links = $this->db->query('SELECT * FROM siteLinks WHERE `updateAt` < NOW() LIMIT 50')->assoc();
        foreach ($links as $link) {
            $linkInfo = parse_url($link['link']);
            $className = ucfirst(preg_replace('/[^\w]|www/', '', strtolower($linkInfo['host']))) . 'Parser';
            $linkParser = $this->createParserClass($className);
            $parseResult = $linkParser->parse();

            $updateAt = $this->calculateNewUpdateDate($link['addDate']);

            $this->db->query(
                'UPDATE sites set `lastUpdateDate` = NOW() WHERE `id` = ?string:id',
                ['id' => $site['id']]
            );
        }
    }

    /**
     * @param string $className
     * @return LinkCollectorInterface
     */
    private function createCollectorClass($className)
    {
        if (isset($this->collectors[$className])) {
            if (class_exists($className)) {
                $parser = new $className($this->curlClient, $this->parser);
            } else {
                $parser = new DummyLinkParser();
                $this->logger->error('Class ' . $className . ' does not exist');
            }
            $this->collectors[$className] = $parser;
        }
        return $this->collectors[$className];
    }

    /**
     * @param string $className
     * @return LinkParserInterface
     */
    private function createParserClass($className)
    {
        if (isset($this->parser[$className])) {
            if (class_exists($className)) {
                $parser = new $className($this->curlClient, $this->parser);
            } else {
                $parser = new DummyLinkParser();
                $this->logger->error('Class ' . $className . ' does not exist');
            }
            $this->parser[$className] = $parser;
        }
        return $this->parser[$className];
    }

}