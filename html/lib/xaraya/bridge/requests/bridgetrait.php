<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use xarController;
use xarServer;
use xarSystemVars;
use sys;

sys::import('xaraya.bridge.requests.module');
use Xaraya\Bridge\Requests\ModuleRequest;

/**
 * For documentation purposes only - available via BasicBridgeTrait
 */
interface BasicBridgeInterface
{
    public static function prepareController(string $module = 'base', string $baseUri = ''): void;
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
    public static function prepareController(string $module = 'base', string $baseUri = ''): void
    {
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        xarController::getRequest()->setModule($module);
        // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
        $entryPoint = str_replace(xarServer::getBaseURI(), '', $baseUri);
        //xarSystemVars::set(sys::LAYOUT, 'BaseURI');
        xarSystemVars::set(sys::LAYOUT, 'BaseModURL', $entryPoint);
        xarController::$entryPoint = $entryPoint;
        // @todo get xarServer::getBaseURL() working correctly for ReactPHP etc.
        //sys::import('modules.modules.controllers.router');
        //ModuleRouter::setBaseUri($baseUri);
        //xarController::$buildUri = [static::class, 'buildUri'];
        xarController::$buildUri = [ModuleRequest::class, 'buildModulePath'];
        //xarController::$redirectTo = [ModuleRequest::class, 'redirectTo'];
    }
}
