<?php

namespace uawc;

use go\DB\DB;
use SebastianBergmann\Diff\Differ;

class API
{
    /**
     * @var DB
     */
    private $db;
    /**
     * @var Differ
     *
     */
    private $differ;

    public function __construct(DB $db, Differ $differ)
    {
        $this->db = $db;
        $this->differ = $differ;
    }

    public function siteList()
    {
        $sites = $this->db->query('SELECT * FROM sites')->assoc();

        return json_encode($sites);
    }

    public function linkList($siteId)
    {
        $siteLinks = $this->db->query(
            'SELECT * FROM siteLinks where siteId = ?string:siteId',
            ['siteId' => $siteId]
        )->assoc();

        return json_encode($siteLinks);
    }

    public function deletedLinkList($siteId)
    {
        $siteLinks = $this->db->query(
            'SELECT sl.* 
                     FROM siteLinkContent slc
                     JOIN siteLinks sl ON sl.id = slc.siteLinkId
                     WHERE sl.siteId = ?string:siteId AND slc.isDeleted = 1
                     GROUP by slc.siteLinkId',
            ['siteId' => $siteId]
        )->assoc();

        return json_encode($siteLinks);
    }

    public function modifiedLinkList($siteId)
    {
        $siteLinks = $this->db->query(
            'SELECT sl.* 
                     FROM siteLinkContent slc
                     JOIN siteLinks sl ON sl.id = slc.siteLinkId
                     WHERE sl.siteId = ?string:siteId 
                     GROUP by slc.siteLinkId 
                     HAVING count(*) > 1',
            ['siteId' => $siteId]
        )->assoc();

        return json_encode($siteLinks);
    }

    public function link($siteLinkId, $version = null)
    {
        $query = 'SELECT sl.link, slc.* 
                  FROM siteLinkContent slc
                  JOIN siteLinks sl ON sl.id = slc.siteLinkId
                  WHERE sl.id = ?string:siteLinkId ';
        if (!is_null($version)) {
            $query .= 'AND version = :version';
        }
        $query .=  'ORDER BY version DESC LIMIT 1';

        $link = $this->db->query(
            $query,
            ['siteLinkId' => $siteLinkId, 'version' => $version]
        )->row();

        return json_encode($link);
    }

    public function linkVersionList($siteLinkId)
{        $versionList = $this->db->query(
    'SELECT *
             FROM siteLinkContent slc
             WHERE slc.siteLinkId = ?string:siteLinkId
             ORDER BY version DESC',
            ['siteLinkId' => $siteLinkId]
        )->assoc();

        return json_encode($versionList);
    }

    public function linkDiff($siteLinkId, $versionFrom, $versionTo)
    {
        $versionList = $this->db->query(
            'SELECT version, content
             FROM siteLinkContent slc
             WHERE slc.siteLinkId = ?string:siteLinkId AND version IN (?int:versionFrom, ?int:versionTo)',
            ['siteLinkId' => $siteLinkId, 'versionFrom' => $versionFrom, 'versionTo' => $versionTo]
        )->assoc();

        if (sizeof($versionList) != 2) {
            return null;
        }

        $diff = $this->differ->diff($versionList[0]['content'], $versionList[1]['content']);

        $result = [
            'siteLinkId' => $siteLinkId,
            'versionFrom' => $versionFrom,
            'versionTo' => $versionTo,
            'diff' => $diff
        ];

        return json_encode($result);
    }
}