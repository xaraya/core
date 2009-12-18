<?php
sys::import('xaraya.mapper.routers.routes.base');

class ShortRoute extends xarRoute
{
    protected $validModule  = false;

    protected $moduleKey    = 'module';
    protected $typeKey      = 'type';
    protected $funcKey      = 'func';

    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
//        if (isset($request)) $this->_request = $request;
        if (isset($dispatcher)) $this->dispatcher = $dispatcher;
        parent::__construct($defaults, $dispatcher);
    }

    public function match(xarRequest $request, $partial=false)
    {
        $this->setRequestKeys();

        $parts = array();
        $params = array();
        
        $path = $request->getURL();
        if ($pos = strpos($path, '?')) $path = substr($path, 0, $pos);
        $path = substr($path, strlen(xarServer::getBaseURL() . $request->entryPoint));
        if (empty($path)) return false;
        
        if (!$partial) {
            $path = trim($path, $this->delimiter);
        } else {
            $matchedPath = $path;
        }

        $path = explode($this->delimiter, $path);
        if ($this->dispatcher && $this->dispatcher->isValidModule($path[0])) {
            $request->setModule(array_shift($path));
            $parts[$this->moduleKey] = $request->getModule();
            $this->validModule = true;
        }

//        if (count($path) && !empty($path[0])) {
//            $request->type = array_shift($path);
//            $parts[$this->typeKey] = $request->type;
//        }
//        $request->type = 'user';
        //exit;

        if (count($path) && !empty($path[0])) {
            $request->setFunction(array_shift($path));
            $parts[$this->funcKey] = $request->getFunction();
        }

        if ($numSegs = count($path)) {
            for ($i = 0; $i < $numSegs; $i = $i + 2) {
                $key = urldecode($path[$i]);
                $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
            }
        }
        
        if ($partial) $this->setMatchedPath($matchedPath);
        $this->parts = $parts + $params;
        return $this->parts + $this->defaults;
    }

    /**
     * Assembles user submitted parameters forming a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @param bool $reset Whether to reset the current params
     * @return string Route path with user submitted parameters
     */
    public function encode($data=array(), $reset=false, $encode=true, $partial=false)
    {
        if (!$this->keysSet) $this->setRequestKeys();
        $params = (!$reset) ? $this->parts : array();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            } elseif (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        $params += $this->defaults;

        $url = '';

        if ($this->validModule || !empty($data[$this->moduleKey])) {
            if ($params[$this->moduleKey] != $this->defaults[$this->moduleKey]) {
                $module = $params[$this->moduleKey];
            }
        }
        unset($params[$this->moduleKey]);

        $type = $params[$this->typeKey];
        unset($params[$this->typeKey]);

        $func = $params[$this->funcKey];
        unset($params[$this->funcKey]);

        // Do the rest of the URL parameters
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($encode) $v = urlencode($v);
                    $url .= $this->delimiter . $k;
                    $url .= $this->delimiter . $v;
                }
            } else {
                if ($encode) $value = urlencode($value);
                $url .= $this->delimiter . $key;
                $url .= $this->delimiter . $value;
            }
        }

        if (!empty($url) || $func !== $this->defaults[$this->funcKey]) {
            if ($encode) $func = urlencode($func);
            $url = $this->delimiter . $func . $url;
        }

        if (!empty($url) || $type !== $this->defaults[$this->typeKey]) {
            if ($encode) $type = urlencode($type);
            $url = $this->delimiter . $type . $url;
        }

        if (isset($module)) {
            if ($encode) $module = urlencode($module);
            $url = '/' . $module . $url;
        }

        return ltrim($url, $this->delimiter);
    }

    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name) 
    {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults() 
    {
        return $this->_defaults;
    }

}
?>