<?php
/**
 * Short Route class
 *
 * @package core
 * @subpackage controllers
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.routers.routes.base');

class ShortRoute extends xarRoute
{
    protected $validModule  = false;

    protected $moduleKey    = 'module';
    protected $typeKey      = 'type';
    protected $funcKey      = 'func';

    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
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
}
?>