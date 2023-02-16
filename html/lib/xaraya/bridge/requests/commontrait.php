<?php
/**
 * Handle common requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use xarController;
use xarServer;
use xarSystemVars;
use sys;

/**
 * For documentation purposes only - available via CommonBridgeTrait
 */
interface CommonBridgeInterface extends CommonRequestInterface, DataObjectBridgeInterface, ModuleBridgeInterface, BlockBridgeInterface
{
    public static function prepareController(string $module = 'base', string $baseUri = ''): void;
}

trait CommonBridgeTrait
{
    use CommonRequestTrait;
    use DataObjectBridgeTrait;
    use ModuleBridgeTrait;
    use BlockBridgeTrait;

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
        xarController::$buildUri = [static::class, 'buildModulePath'];
        //xarController::$redirectTo = [static::class, 'redirectTo'];
    }
}
