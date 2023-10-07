<?php
/**
 * Dispatcher  class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarDispatcher extends xarObject
{
    /** @var iController */
    protected $controller;
    /** @var xarRequest */
    protected $request;
    /** @var xarResponse */
    protected $response;

    public function findController(xarRequest $request): iController
    {
        if (file_exists(sys::code() . 'modules/' . $request->getModule() . '/controllers/' . $request->getRoute() . '.php')) {
            sys::import('modules.' . $request->getModule() . '.controllers.' . $request->getRoute());
            $controllername = UCFirst($request->getModule()) . UCFirst($request->getRoute()) . 'Controller';
        } else {
            sys::import('xaraya.mapper.controllers.' . $request->getRoute());
            $controllername = UCFirst($request->getRoute()) . 'ActionController';
        }
        $controller = new $controllername($request);
        $request->setActionString($controller->getActionString($request));
        return $controller;
    }

    public function dispatch(xarRequest $request, xarResponse $response): string
    {
        $this->response = $response;
        $this->controller = $this->findController($request);
        $this->controller->run($request, $response);
        return $response->getOutput();
    }

    public function isValidModule(string $module): bool
    {
        if (empty($module)) {
            return false;
        }
        $available = xarMod::isAvailable($module);
        return $available;
    }

    public function getController(): iController
    {
        return $this->controller;
    }

    public function getRequest(): xarRequest
    {
        return $this->request;
    }
}
