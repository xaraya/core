<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use xarController;
use xarServer;
use xarSystemVars;
use xarTpl;
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
    /**
     * Summary of prepareController
     * @param string $module
     * @param string $baseUri
     * @return void
     */
    public function prepareController(string $module = 'base', string $baseUri = ''): void
    {
        // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
        $entryPoint = str_replace(xarServer::getBaseURI(), '', $baseUri);
        //xarSystemVars::set(sys::LAYOUT, 'BaseURI');
        xarSystemVars::set(sys::LAYOUT, 'BaseModURL', $entryPoint);
        xarController::$entryPoint = $entryPoint;
        // @todo get xarServer::getBaseURL() working correctly for ReactPHP etc.
        //sys::import('xaraya.bridge.middleware.modules.router');
        //ModuleRouter::setBaseUri($baseUri);
        xarController::setCallback('buildUri', [$this, 'buildUri']);
        //xarController::$buildUri = [ModuleRequestHandler::class, 'buildModulePath'];
        //xarController::$redirectTo = [ModuleRequestHandler::class, 'redirectTo'];
        // Note: do this after updating controller entryPoint, so that request entryPoint matches
        xarController::getRequest()->setEntryPoint(xarController::$entryPoint);
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        xarController::getRequest()->setModule($module);
    }

    /**
     * Summary of wrapOutputInPage
     * @param string $body
     * @param mixed $context
     * @return string
     */
    public function wrapOutputInPage(string $body, $context = null): string
    {
        // Render page with the output - see index.php
        return xarTpl::renderPage($body, null, $context);
    }
}
