<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Services\WithServicesClass;
use xarSystemVars;
use sys;

/**
 * For documentation purposes only - available via BasicBridgeTrait
 */
interface BasicBridgeInterface
{
    public function prepareController(string $module = 'base', string $baseUri = ''): void;
    public function wrapOutputInPage(string $body, $context = null): string;
}

/**
 * Bridge for generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
trait BasicBridgeTrait
{
    use WithServicesClass;

    /**
     * Summary of prepareController
     * @param string $module
     * @param string $baseUri
     * @return void
     */
    public function prepareController(string $module = 'base', string $baseUri = ''): void
    {
        $ctl = $this->getServicesClass()->ctl();
        // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
        $entryPoint = str_replace($ctl->getBaseURI(), '', $baseUri);
        $sysLayout = $this->getServicesClass()->sysConfig(sys::LAYOUT);
        $sysLayout->setVar('BaseModURL', $entryPoint);
        $ctl->setEntryPoint($entryPoint);
        // @todo get $ctl->getBaseURL() working correctly for ReactPHP etc.
        //sys::import('xaraya.bridge.middleware.modules.router');
        //ModuleRouter::setBaseUri($baseUri);
        $ctl->setCallback('buildUri', [$this, 'buildUri']);
        //$ctl->setCallback('redirectTo', [$this, 'redirectTo']);
        // Note: do this after updating controller entryPoint, so that request entryPoint matches
        $request = $ctl->getRequest();
        $request->setEntryPoint($ctl->getEntryPoint());
        // set current module to 'module' for Xaraya controller - used e.g. in xar::mod()->getName()
        $request->setModule($module);
    }

    /**
     * Summary of wrapOutputInPage
     * @param string $body
     * @param mixed $context
     * @return string
     */
    public function wrapOutputInPage(string $body, $context = null): string
    {
        $xar = $this->getServicesClass();
        if (!empty($context)) {
            $xar->setContext($context);
        }
        // Render page with the output - see index.php
        return $xar->tpl()->renderPage($body);
    }
}
