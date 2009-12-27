<?php
/**
 * Base Router class
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage controllers
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarRouter extends Object
{
    protected $routes       = array();
    protected $currentRoute = 'default';
    protected $globalParams = array();
    
    public function addRoute($name, xarRoute $route) 
    {
        $this->routes[$name] = $route;
        
        return true;
    }

    public function addDefaultRoutes()
    {
        if (empty($this->routes['default'])) {
            $dispatcher = xarController::getDispatcher();

            sys::import('xaraya.mapper.routers.routes.default');
            $route = new DefaultRoute(array(), $dispatcher);
            $this->routes['default'] = $route;

            sys::import('xaraya.mapper.routers.routes.short');
            $route = new ShortRoute(array(), $dispatcher);
            $this->routes['short'] = $route;

            sys::import('xaraya.mapper.routers.routes.hostname');
            $route = new HostnameRoute(array(), $dispatcher);
            $this->routes['hostname'] = $route;

            sys::import('xaraya.mapper.routers.routes.static');
            $route = new StaticRoute(array(), $dispatcher);
            $this->routes['static'] = $route;
        }
        
        return $this;
    }

    public function route(xarRequest $request)
    {
        $this->addDefaultRoutes();
        foreach (array_reverse($this->routes) as $name => $route) {
            if ($params = $route->match($request)) {
                $request->setRoute($name);
                $this->currentRoute = $name;
                return true;
            }
        }
        return false;
    }

    public function assemble($userParams=array(), $name=null, $reset=false, $encode=true)
    {
        if ($name == null) {
            $name = isset($this->currentRoute) ? $this->currentRoute : 'default';
        }
        
        $params = array_merge($this->globalParams, $userParams);
        
        $route = $this->getRoute($name);
        $url   = $route->assemble($params, $reset, $encode);

        if (!preg_match('|^[a-z]+://|', $url)) {
            $url = rtrim(xarServer::getBaseURL(), xarController::$delimiter) . xarController::$delimiter . $url;
        }

        return $url;
    }

    public function getRoute($name=null)
    {
        if (null == $name) return $this->currentRoute;
        return $this->routes[$name];
    }

    protected function setRequestParams(xarRequest $request, $params)
    {
        foreach ($params as $key => $value) {
            if ($key === 'module') $request->module = $value;
            if ($key === 'type')   $request->type   = $value;
            if ($key === 'func')   $request->func   = $value;
        }
    }

}
?>