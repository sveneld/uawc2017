<?php

namespace uawc;

use go\DB\DB;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Diff\Differ;
use uawc\Collector\DummyLinkCollector;
use uawc\Collector\LinkCollectorInterface;
use uawc\Parser\DummyLinkParser;
use uawc\Parser\LinkParserInterface;

class Cron
{
    /**
     * @var \simple_html_dom
     */
    private $parser;
    /**
     * @var \HTMLPurifier
     */
    private $purifier;
    /**
     * @var Client
     */
    private $curlClient;
    /**
     * @var DB
     */
    private $db;
    /**
     * @var Differ
     */
    private $differ;
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
        \HTMLPurifier $purifier,
        Client $curlClient,
        DB $db,
        Differ $differ,
        LoggerInterface $logger
    ) {
        $this->parser = $parser;
        $this->purifier = $purifier;
        $this->curlClient = $curlClient;
        $this->db = $db;
        $this->differ = $differ;
        $this->logger = $logger;
    }

    public function collectLinks()
    {
        $sites = $this->db->query(
            'SELECT * FROM sites WHERE `lastUpdateDate` > ?string:lastUpdateDate LIMIT 10',
            ['lastUpdateDate' => (new \DateTimeImmutable('1 day ago'))->format('Y-m-d H:i:s')]
        )->assoc();

        foreach ($sites as $site) {
            $linkInfo = parse_url($site['link']);
            $className = ucfirst(preg_replace('/[^\w]|www/', '', strtolower($linkInfo['host']))) . 'Collector';

            $linkCollector = $this->createCollectorClass(__NAMESPACE__ . '\\Collector\\' . $className);
            $links = $linkCollector->collect();

            $insertData = [];
            $addDate = new \DateTimeImmutable();
            $updateAt = $addDate->add(new \DateInterval('PT5M'));
            foreach ($links as $link) {
                $insertData[] = [
                    md5($link), //id
                    $site['id'], //siteId
                    $link, //link
                    $addDate->format('Y-m-d H:i:s'), //addDate
                    $updateAt->format('Y-m-d H:i:s'), //updateAt
                ];
            }
            if (!empty($links)) {
                $this->db->query(
                    'INSERT OR IGNORE INTO siteLinks (`id`, `siteId`, `link`, `adDdate`, `updateAt`) VALUES ?values',
                    [$insertData]
                );
            }

            $this->db->query(
                'UPDATE sites SET `lastUpdatedAt` = DATETIME() WHERE `id` = ?string:id',
                ['id' => $site['id']]
            );
        }
    }

    public function parseLinks()
    {
        $links = $this->db->query('SELECT * FROM siteLinks WHERE `updateAt` < DATETIME() LIMIT 5')->assoc();
        foreach ($links as $link) {
            $linkInfo = parse_url($link['link']);
            $className = ucfirst(preg_replace('/[^\w]|www/', '', strtolower($linkInfo['host']))) . 'Parser';
            $linkParser = $this->createParserClass(__NAMESPACE__ . '\\Parser\\' . $className);
            $content = $linkParser->parse($link['link']);
            $version = 0;

            $previousContent = $this->db->query(
                'SELECT * FROM siteLinkContent WHERE `siteLinkId` = ?string:siteLinkId ORDER BY version DESC',
                ['siteLinkId' => $link['id']]
            )->row();

            if (!empty($previousContent)) {
                $diff = $this->differ->diff($previousContent['content'], $content);
                print_r($diff);
                die();
                if (empty($diff)) {
                    continue;
                }
                $version = $previousContent['version'];
            }

            $addDate = new \DateTimeImmutable();
            $insertData = [];
            $insertData[] = [
                md5($version . $addDate->format('Y-m-d H:i:s') . $link['id']), //id
                $link['id'], //siteLinkId
                $content, //content
                $addDate->format('Y-m-d H:i:s'), //adDdate
                (int)$version + 1, //version
            ];
            $this->db->query(
                'INSERT INTO siteLinkContent (`id`, `siteLinkId`, `content`, `adDdate`, `version`) VALUES ?v',
                [$insertData]
            );

            $updateAt = $this->calculateNewUpdateAtDate(new \DateTimeImmutable($link['addDate']));

            $this->db->query(
                'UPDATE siteLinks SET `updateAt` = ?string:updateAt WHERE `id` = ?string:id',
                [
                    'id' => $link['siteId'],
                    'updateAt' => $updateAt->format('Y-m-d H:i:s'),
                ]
            );
        }
    }

    /**
     * @param string $className
     * @return LinkCollectorInterface
     */
    private function createCollectorClass($className)
    {
        if (!isset($this->collectors[$className])) {
            if (class_exists($className)) {
                $parser = new $className($this->curlClient, $this->parser, $this->logger);
            } else {
                $parser = new DummyLinkCollector();
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
        if (!isset($this->parsers[$className])) {
            if (class_exists($className)) {
                $parser = new $className($this->curlClient, $this->parser, $this->purifier, $this->logger);
            } else {
                $parser = new DummyLinkParser();
                $this->logger->error('Class ' . $className . ' does not exist');
            }
            $this->parsers[$className] = $parser;
        }

        return $this->parsers[$className];
    }

    /**
     * @param \DateTimeImmutable $date
     * @return \DateTimeImmutable
     */
    private function calculateNewUpdateAtDate(\DateTimeImmutable $date)
    {
        $daysDiff = $date->diff(new \DateTimeImmutable())->format('a');
        if ($daysDiff < 7) {
            $updateAt = $date->add(new \DateInterval('P1D'));
        } elseif ($daysDiff < 14) {
            $updateAt = $date->add(new \DateInterval('P5D'));
        } elseif ($daysDiff < 30) {
            $updateAt = $date->add(new \DateInterval('P30D'));
        } else {
            $updateAt = $date->add(new \DateInterval('P60D'));
        }

        return $updateAt;
    }
}