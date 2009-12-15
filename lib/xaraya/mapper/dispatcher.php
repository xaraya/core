<?php
class xarDispatcher extends Object
{
    protected $controller;
    protected $request;
    protected $response;

/*    function __construct($request=null)
    {
        if (empty($request)) $request = new xarRequest();
    }
*/    
    public function findController(xarRequest $request)
    {
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $coredirectory = realpath(sys::root() . 'lib/xaraya/mapper');
        if ($request->getRoute() == 'default') {
            sys::import('xaraya.mapper.default');
            $controller = new DefaultActionController();
        } elseif (file_exists($coredirectory . '/' . $request->getModule() . '.php')) {
            sys::import('xaraya.mapper.' .$request->getModule());
            $controllername = ucfirst($request->getModule()) . 'ActionController';
            $controller = new $controllername($request);
            $initialpath .= $request->delimiter . $request->getModule();
        } elseif (file_exists(sys::code() . '/modules/' . $request->getModule() . '/controller.php')) {
            sys::import('modules.' . $request->getModule() . '.controller');
            $controllername = ucfirst($request->getModule()) . 'ActionController';
            $controller = new $controllername($request);
            $initialpath .= $request->delimiter . $request->getModule() . $request->delimiter;
        } else {            
            // This is either an unknown route or an empty route for now
            // Send 404
            sys::import('xaraya.mapper.default');
            $controller = new ActionController($request);
        }
        $actionstring = substr($request->getURL(), strlen($initialpath));
        $request->setActionString($actionstring);
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