<?php
/**
 * OpenSKOS
*
* LICENSE
*
* This source file is subject to the GPLv3 license that is bundled
* with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://www.gnu.org/licenses/gpl-3.0.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@zend.com so we can send you a copy immediately.
*
* @category   OpenSKOS
* @package    OpenSKOS
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Mark Lindeman
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class OaiPmh_IndexController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf8');
    }

    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $repository = new OpenSkos2\OaiPmh\Repository(
            $this->getDI()->get('\OpenSkos2\ConceptManager'),
            $this->getDI()->get('\OpenSkos2\ConceptSchemeManager'),
            $this->getDI()->get('\OpenSkos2\Search\Autocomplete'),
            'OpenSKOS - OAI-PMH Service provider',
            $this->getBaseUrl(),
            ['oai-pmh@openskos.org'],
            new \OpenSKOS_Db_Table_Collections(),
            null
        );

        $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $provider = new Picturae\OaiPmh\Provider($repository, $request);
        $response = $provider->getResponse();
        (new Zend\Diactoros\Response\SapiEmitter())->emit($response);
    }

    public function getAction()
    {
        $this->_501('GET');
    }

    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('POST');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }

    /**
     * Get base url
     * @return string
     */
    private function getBaseUrl()
    {
        return $this->view->serverUrl() . $this->view->url();
    }
}
