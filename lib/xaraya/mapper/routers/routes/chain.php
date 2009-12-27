<?php
sys::import('xaraya.mapper.routers.routes.base');

class ChainRoute extends xarRoute
{
    protected $routes = array();
    protected $separators = array();
    
    /**
     * Add a route to this chain
     * 
     * @param  xarRoute $route
     * @param  string   $separator
     * @return boolean
     */
    public function chain(xarRoute $route, $separator='/')
    {
        $this->routes[]     = $route;
        $this->separators[] = $separator;

        return true;
    }

    /**
     * Matches a user submitted path with a previously defined route.
     * Assigns and returns an array of defaults on a successful match.
     *
     * @param  xarRequest $request Request to get the path info from
     * @return array|false An array of assigned values or a false on a mismatch
     */
    public function match(xarRequest $request, $partial=false)
    {
        $path = trim($request->getURL(), '/');
        $subPath = $path;
        $values  = array();

        foreach ($this->routes as $key => $route) {
            if ($key > 0 && $matchedPath !== null) {
                $separator = substr($subPath, 0, strlen($this->separators[$key]));
                
                if ($separator !== $this->separators[$key]) {
                    return false;                
                }
                
                $subPath = substr($subPath, strlen($separator));
            }
            
            $request->setURL($subPath);
            $match = $request;                
            
            $res = $route->match($match, true);
            if ($res === false) {
                return false;
            }
            
            $matchedPath = $route->getMatchedPath();
            
            if ($matchedPath !== null) {
                $subPath     = substr($subPath, strlen($matchedPath));
                $separator   = substr($subPath, 0, strlen($this->separators[$key]));
            }

            $values = $res + $values;
        }
        
        $path = $request->getURL();
        
        if ($subPath !== '' && $subPath !== false) return false;

        return $values;
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
        $url     = '';
        $numRoutes = count($this->routes);
        
        foreach ($this->routes as $key => $route) {
            if ($key > 0) {
                $url .= $this->separators[$key];
            }
            
            $url .= $route->assemble($data, $reset, $encode, (($numRoutes - 1) > $key));
            
            if (method_exists($route, 'getVariables')) {
                $variables = $route->getVariables();
                
                foreach ($variables as $variable) {
                    $data[$variable] = null;
                }
            }
        }

        return $url;
    }
    */
}
?>