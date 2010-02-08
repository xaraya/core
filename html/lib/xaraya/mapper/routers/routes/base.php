<?php

sys::import('xaraya.mapper.routers.routes.interfaces');

class xarRoute extends Object implements iRoute
{
    protected $delimiter = "/";
    protected $request;
    protected $dispatcher;
    protected $route = null;
    protected $parts = array();
    protected $defaults = array();
    protected $keysSet     = false;
    
    protected $moduleKey = 'module';
    protected $typeKey   = 'type';
    protected $funcKey   = 'func';

    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
        $this->defaults += $defaults;
        if (isset($request)) $this->request = $request;
        if (isset($dispatcher)) $this->dispatcher = $dispatcher;
//        $this->route = trim($this->route, '/');
    }

    /**
     * Set request keys based on values in request object
     *
     * @return void
     */
    protected function setRequestKeys()
    {
        if (null !== $this->request) {
            if ($this->request->moduleKey) $this->moduleKey   = $this->request->moduleKey;
            if ($this->request->typeKey) $this->typeKey     = $this->request->typeKey;
            if ($this->request->funcKey) $this->funcKey     = $this->request->funcKey;
        }

        $this->defaults += array(
            $this->moduleKey   => xarController::$module,
            $this->typeKey     => xarController::$type,
            $this->funcKey     => xarController::$func,
        );

        $this->keysSet = true;
    }

    public function match(xarRequest $request, $partial=false)
    {
        $path = $request->getURL();
        if ($partial) {
            if (substr($path, 0, strlen($this->route)) === $this->route) {
                $this->setMatchedPath($this->_route);
                return $this->defaults;
            }
        } else {
            if (trim($path, $this->delimiter) == $this->route) {
                return $this->defaults;
            }
        }
        
        return false;
    }

    /**
     * Assembles a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @return string Route path with user submitted parameters
     */
     /*
    public function encode($data=array(), $reset=false, $encode=true, $partial=false)
    {
        return $this->route;
    }
*/
}
?>