<?php

/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\OaiPmh;

class Repository implements \Picturae\OaiPmh\Interfaces\Repository
{
    /**
     * OAI-PMH Repository name
     * @var string
     */
    private $repositoryName;

    /**
     * Base url for OAI-PMH
     * @var string
     */
    private $baseUrl;

    /**
     * Admin emails
     *
     * @var string[]
     */
    private $adminEmails = [];

    /**
     * Optional description for the repository
     *
     * @var string|null
     */
    private $description;

    /**
     * a datetime that is the guaranteed lower limit of all datestamps recording changes,modifications, or deletions
     * in the repository. A repository must not use datestamps lower than the one specified
     * by the content of the earliestDatestamp element. earliestDatestamp must be expressed at the finest granularity
     * supported by the repository.
     *
     * @return \DateTime
     */
    private $earliestDateStamp;

    /**
     * RDF Resource manager
     *
     * @var \OpenSkos2\Rdf\ResourceManager
     */
    private $resourceManager;

    /**
     * Current offset retrieved from token
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Database adapter
     *
     * @var \Zend_Db_Adapter_Abstract $db,
     */
    private $db;
    
    /**
     *
     * @var \OpenSkos2\ConceptSchemeManager
     */
    private $schemeManager;

    public function __construct(
        \OpenSkos2\Rdf\ResourceManager $resourceManager,
        \OpenSkos2\ConceptSchemeManager $schemeManager,
        $repositoryName,
        $baseUrl,
        array $adminEmails,
        \Zend_Db_Adapter_Abstract $db,
        $description = null
    ) {
        $this->resourceManager = $resourceManager;
        $this->schemeManager = $schemeManager;
        $this->repositoryName = $repositoryName;
        $this->baseUrl = $baseUrl;
        $this->adminEmails = $adminEmails;
        $this->description = $description;
        $this->db = $db;
        $this->getEarliestDateStamp();
    }

    /**
     * @return Identity
     */
    public function identify()
    {
        return new \Picturae\OaiPmh\Implementation\Repository\Identity(
            $this->repositoryName,
            $this->baseUrl,
            $this->getEarliestDateStamp(),
            \Picturae\OaiPmh\Interfaces\Repository\Identity::DELETED_RECORD_NO,
            $this->adminEmails,
            \Picturae\OaiPmh\Implementation\Repository\Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ,
            null,
            null
        );
    }

    /**
     * @return SetList
     */
    public function listSets()
    {
        $collections = $this->getCollections();
        
        $items = [];
        
        $tenantAdded = [];
        
        foreach ($collections as $row) {
            // Tenant spec
            $tenantCode = $row['tenant_code'];
            if (!isset($tenantAdded[$tenantCode])) {
                $items[] = new \Picturae\OaiPmh\Implementation\Set($tenantCode, $row['tenant_title']);
                $tenantAdded[$tenantCode] = $tenantCode;
            }
            
            // Collection spec
            $spec = $row['tenant_code'] . ':' . $row['collection_code'];
            $items[] = new \Picturae\OaiPmh\Implementation\Set($spec, $row['collection_title']);
                        
            // Concept scheme spec
            $schemes = $this->schemeManager->getSchemeByCollectionUri($row['collection_uri']);
            foreach ($schemes as $scheme) {
                $uri = $scheme->getUri();
                $pUri = parse_url($uri);
                $schemeSpec = $spec . ':' . $pUri['host'] . $pUri['path'];
                $title = $scheme->getProperty(\OpenSkos2\Namespaces\DcTerms::TITLE);
                $name = $title[0]->getValue();
                $items[] = new \Picturae\OaiPmh\Implementation\Set($schemeSpec, $name);
            }
        }

        return new \Picturae\OaiPmh\Implementation\SetList($items);
    }

    /**
     * @param string $token
     * @return SetList
     */
    public function listSetsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);
        return $this->listSets();
    }

    /**
     * @param string $metadataFormat
     * @param string $identifier
     * @return Record
     */
    public function getRecord($metadataFormat, $identifier)
    {

    }

    /**
     * @param string $metadataFormat metadata format of the records to be fetch or null if only headers are fetched
     * (listIdentifiers)
     * @param \DateTime $from
     * @param \DateTime $until
     * @param string $set name of the set containing this record
     * @return RecordList
     */
    public function listRecords($metadataFormat = null, \DateTime $from = null, \DateTime $until = null, $set = null)
    {

    }

    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken($token)
    {

    }

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats($identifier = null)
    {

    }

    /**
     * @todo Figure out what kind of dom document this needs to be
     * @return \OpenSkos2\OaiPmh\DOMDocument
     */
    private function getDescription()
    {
        $doc = new \DOMDocument();
        return $doc;
    }

    /**
     * Get resumption token
     *
     * @param int $offset
     * @param \DateTime $from
     * @param \DateTime $till
     * @param string $metadataPrefix
     * @param string $set
     * @return string
     */
    private function getResumptionToken(
        $offset = 0,
        \DateTime $from = null,
        \DateTime $till = null,
        $metadataPrefix = null,
        $set = null
    ) {
        $params = [];
        $params['offset'] = $offset;
        $params['metadataPrefix'] = $metadataPrefix;
        $params['set'] = $set;

        if ($from) {
            $params['from'] = $from->getTimestamp();
        }

        if ($till) {
            $params['till'] = $till->getTimestamp();
        }

        return base64_encode(json_encode($params));
    }

    /**
     * Get all collections
     *
     * @return \OpenSKOS_Db_Table_Row_Collection[]
     */
    private function getCollections()
    {
        $sql = $this->db->select()
                ->from(['col' => 'collection'], [
                    'collection_code' => 'col.code',
                    'collection_title' => 'col.dc_title',
                    'collection_description' => 'col.dc_description',
                    'collection_uri' => 'col.uri',
                ])
                ->join(
                    ['ten' => 'tenant'],
                    'col.tenant = ten.code',
                    [
                        'tenant_title' => 'ten.name',
                        'tenant_code' => 'ten.code',
                    ]
                )
                ->order('col.tenant ASC');
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Decode resumption token
     * possible properties are:
     *
     * ->offset
     * ->metadataPrefix
     * ->set
     * ->from
     * ->till
     *
     * @param string $token
     * @return \stdClass
     */
    private function decodeResumptionToken($token)
    {
        return (array)base64_decode(json_decode($params));
    }

    /**
     * Get earliest modified timestamp
     *
     * @return string returns date/time like 2015-01-28T10:05:07.438Z
     */
    private function getEarliestDateStamp()
    {
        if ($this->earliestDateStamp) {
            return $this->earliestDateStamp;
        }

        $query = 'PREFIX dcterms: <http://purl.org/dc/terms/>
            SELECT ?date
                WHERE {
                    ?subject dcterms:modified ?date
                }
                ORDER BY ASC(?date)
                LIMIT 1
            ';

        $graph = $this->resourceManager->query($query);
        return new \DateTime($graph[0]->date->getValue());
    }
}
