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

namespace OpenSkos2\Api;

/**
 * API Concept result set
 * The result set for the API needs the total items found
 */
class ConceptResultSet
{
    /**
     * Total
     * @var int
     */
    private $total;
    
    /**
     * Concept
     * @var \OpenSkos2\ConceptCollection
     */
    private $concepts;
    
    /**
     * Offset from total result
     *
     * @var int
     */
    private $start;
    
    /**
     * Number of rows limit
     *
     * @var int
     */
    private $limit;
    
    /**
     * @param \OpenSkos2\ConceptCollection $concepts
     * @param int $total
     * @param int $start
     * @param int $limit
     */
    public function __construct(\OpenSkos2\ConceptCollection $concepts, $total, $start, $limit)
    {
        $this->concepts = $concepts;
        $this->total = $total;
        $this->start = $start;
        $this->limit = $limit;
    }

    /**
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }
    
    /**
     *
     * @return \OpenSkos2\ConceptCollection
     */
    public function getConcepts()
    {
        return $this->concepts;
    }
    
    /**
     * Get offset
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }
    
    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
