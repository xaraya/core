<?php
/**
 * Dispatcher  class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarDispatcher extends Object
{
    protected $controller;
    protected $request;
    protected $response;

    public function findController(xarRequest $request)
    {
        if (file_exists(sys::code() . 'modules/' . $request->getModule() . '/controllers/' . $request->getRoute() . '.php')) {
            sys::import('modules.' . $request->getModule() . '.controllers.' . $request->getRoute());
            $controllername = UCFirst($request->getModule()) . UCFirst($request->getRoute()) . 'Controller';
            $controller = new $controllername($request);
        } else {
            sys::import('xaraya.mapper.controllers.' . $request->getRoute());
            $controllername = UCFirst($request->getRoute()) . 'ActionController';
            $controller = new $controllername($request);
        }
        $request->setActionString($controller->getActionString($request));
        return $controller;
    }

    public function dispatch(xarRequest $request, xarResponse $response)
    {
        $this->response = $response;
        $this->controller = $this->findController($request);
        $this->controller->run($request, $response);
        return $response->output;
    }

    public function isValidModule($module)
    {
        if (!is_string($module)) return false;
        $available = xarModIsAvailable($module);
        return $available;
    }

    function getController()  { return $this->controller; }
    function getRequest()     { return $this->request; }
}
?>