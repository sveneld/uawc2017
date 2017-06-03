<?php


//class Cron
//{
//    private $links = [];
//    /**
//     * @var \simple_html_dom
//     */
//    private $parser;
//    /**
//     * @var Client
//     */
//    private $curlClient;
//    /**
//     * @var DB
//     */
//    private $db;
//
//    public function __construct(
//        \simple_html_dom $parser,
//        Client $curlClient,
//        DB $db
//    ) {
//        $this->parser = $parser;
//        $this->curlClient = $curlClient;
//        $this->db = $db;
//    }
//
//    public function run()
//    {
//        $this->getLinks();
//        $this->parseLinks();
//        $this->saveResults();
//    }
//
//    private function getLinks()
//    {
//        $query = vam_db_query("
//            SELECT pl.products_links_id, pl.products_id, pl.link, pd.products_name
//            FROM " . TABLE_PRODUCTS_LINKS . " pl
//            LEFT JOIN (
//                 SELECT MAX(time) as time, products_links_id
//                 FROM " . TABLE_PRODUCTS_LINKS_RESULTS . "
//                 GROUP BY products_links_id
//            ) plr ON plr.products_links_id = pl.products_links_id
//            LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = pl.products_id
//                AND pd.language_id = (SELECT languages_id FROM " . TABLE_LANGUAGES . " WHERE code ='" . DEFAULT_LANGUAGE . "')
//            WHERE plr.time < " . strtotime('4 week ago') . " OR plr.time IS NULL
//            ORDER BY RAND()
//            LIMIT 10
//            "
//        );
//        while ($link = vam_db_fetch_array($query)) {
//            $this->links[] = $link;
//        }
//    }
//
//    private function parseLinks()
//    {
//        foreach ($this->links as &$link) {
//            $linkInfo = parse_url($link['link']);
//            $className = ucfirst(preg_replace('/[^\w]|www/', '', strtolower($linkInfo['host']))) . 'Parser';
//            if (class_exists($className)) {
//                $html = $this->getHtmlDom($link['link']);
//                $this->parser->load($html);
//                /**
//                 * @var $parser ParserInterface
//                 */
//                $parser = new $className();
//                try {
//                    $link['price'] = $parser->getPrice($this->parser);
//                } catch (\Throwable $e) {
//                    $link['error'] = $e->getMessage();
//                    continue;
//                }
//            } else {
//                $link['error'] = 'Class ' . $className . ' does not exist';
//            }
//        }
//        unset($link);
//    }
//
//    private function getHtmlDom($link)
//    {
//        $headers = [];
//        $headers[] = 'Cache-Control: no-cache';
//        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0';
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $link);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        $result = curl_exec($ch);
//        curl_close($ch);
//
//        return $result;
//    }
//
//    private function saveResults()
//    {
//        foreach ($this->links as &$link) {
//        }
//    }
//}
//
//$params = [
//    'host' => 'localhost',
//    'username' => 'test',
//    'password' => 'test',
//    'dbname' => 'test',
//    'charset' => 'utf8',
//    '_debug' => true,
//    '_prefix' => 'p_',
//];
//
//$db = DB::create($params, 'mysql');
//
//$client = new Client();
//
//$cron = new Cron(
//    new \simple_html_dom(),
//    $client,
//    $db
//);
//$cron->run();


//$params = array(
//    'filename' => 'DB.sql',
//    'mysql_quot' => true,
//);
//$db = DB::create($params, 'sqlite');
//
//$db->query('CREATE TABLE IF NOT EXISTS `sites` (
//  `id` char(32) NOT NULL,
//  `link` varchar(500) NOT NULL,
//  `lastUpdatedAt` DATETIME NULL,
//  PRIMARY KEY (`id`)
//);');
//
//$db->query('CREATE TABLE IF NOT EXISTS `siteLinks` (
//  `id` char(32) NOT NULL,
//  `siteId` char(32) NOT NULL,
//  `link` varchar(500) NOT NULL,
//  `addDate` DATETIME NOT NULL,
//  `updateAt` DATETIME NOT NULL,
//  PRIMARY KEY (`id`)
//);');
//
//$db->query('CREATE INDEX siteId ON siteLinks (siteId)');
//
//$db->query('CREATE TABLE IF NOT EXISTS `siteLinkContent` (
//  `id` char(32) NOT NULL,
//  `idSiteLink` char(32) NOT NULL,
//  `content` TEXT,
//  `version` TINYINT,
//  `addDate` DATETIME NULL,
//  PRIMARY KEY (`id`)
//);');

namespace uawc;

require 'vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->setPsr4("uawc\\", [__DIR__.'/src']);
$classLoader->register(true);

use go\DB\DB;
use GuzzleHttp\Client;
use Psr\Log\NullLogger;
use SebastianBergmann\Diff\Differ;

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

$params = array(
    'filename' => 'DB.sql',
    'mysql_quot' => true,
);
$db = DB::create($params, 'sqlite');

$config = \HTMLPurifier_Config::createDefault();
$config->set('AutoFormat.AutoParagraph', true);
$config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
$purifier = new \HTMLPurifier($config);



$cron = new Cron(new \simple_html_dom(),  new \HTMLPurifier, new Client(), $db, new Differ('', false), new NullLogger());

$cron->parseLinks();