<?php
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