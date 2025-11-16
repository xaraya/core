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
        $result = xarClassMap::findController($request->getModule(), $request->getRoute());
        if (!empty($result)) {
            require_once($result['filepath']);
            $controllername = $result['classname'];
        } else {
            $controllername = UCFirst($request->getRoute()) . 'ActionController';
        }
        /** @var iController $controller */
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

    /**
     * @deprecated 2.8.8 moved to xarRequest for ShortRoute
     */
    public function isValidModule(xarRequest $request, string $module): bool
    {
        return $request->isValidModule($module);
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
