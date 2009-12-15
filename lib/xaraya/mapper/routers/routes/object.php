<?php
sys::import('xaraya.mapper.routers.routes.short');

class ObjectRoute extends ShortRoute
{
    protected $validModule  = false;

    protected $moduleKey    = 'module';
    protected $typeKey      = 'object';
    protected $funcKey      = 'method';

    public function match(xarRequest $request, $partial=false)
    {
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
        if ($path[0] == 'object') {
            $parts[$this->moduleKey] = array_shift($path);
            $this->validModule = true;
        } else {
            return false;
        }

        if (count($path) && !empty($path[0])) {
            $parts[$this->typeKey] = array_shift($path);
        }

        if (count($path) && !empty($path[0])) {
            $parts[$this->funcKey] = array_shift($path);
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
}
?>