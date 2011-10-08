<?php
/**
 * Short Route class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

/**
 * This route assumes a URL of the form
 *
 * [protocol][host][entrypoint] /part1/part2... ? [param1]=[value1]&[param2]=[value2]...
 *
 * 1. Well formed protocol, host and entry point are required
 * 2. The entry point is folowed by one or more parts separated by slashes ("/")
 * 3. The first part encountered is considered to indicate the module and is validated as such
 * 4. The second part encountered is considered to indicate the function
 * 5. Subsequent parts are added in pairs, where the first is assumed to be a key and the second a value
 * 6. The type is ignored. If "admin" is present as the function then the encode/decode methods will treat this as a backend URL
 * 7. Further key/value pairs can be added after the "?"
**/

sys::import('xaraya.mapper.routers.routes.base');

class ShortRoute extends xarRoute
{
    protected $validModule  = false;

    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
        if (isset($dispatcher)) $this->dispatcher = $dispatcher;
        parent::__construct($defaults, $dispatcher);
    }

    public function match(xarRequest $request, $partial=false)
    {
        // Set the keys for module/type/func as per the current request, and the default values in xarController
        $this->setRequestKeys();

        // Get the request's URL string
        $path = $request->getURL();

        $params = array();
        $parts = array();
        
        // Get everything between the entry point and the beginning of the query part of the URL
        if ($pos = strpos($path, '?')) $path = substr($path, 0, $pos);
        $path = substr($path, strlen(xarServer::getBaseURL() . $request->entryPoint));
        if (empty($path)) return false;
        
        if (!$partial) {
            $path = trim($path, $this->delimiter);
        } else {
            $matchedPath = $path;
        }

        // Get the module part and validate it. Can be an alias; the dispatcher should know
        $path = explode($this->delimiter, $path);
        if ($this->dispatcher && $this->dispatcher->isValidModule($path[0])) {
            $request->setModule(array_shift($path));
            $parts[$this->moduleKey] = $request->getModule();
            $this->validModule = true;
        }

        // if the next part is admin, set type
        // <chris/> this is a temp fix, to be addressed in mapper2 
        if (count($path) && !empty($path[0]) && $path[0] == 'admin') {
            $request->setType(array_shift($path));
            $parts[$this->typeKey] = $request->getType();
        }
        
        // Get the function part
        if (count($path) && !empty($path[0])) {
            $request->setFunction(array_shift($path));
            $parts[$this->funcKey] = $request->getFunction();
        }

        // Get any more parts as key/value pairs separated by "/"
        if ($numSegs = count($path)) {
            for ($i = 0; $i < $numSegs; $i = $i + 2) {
                $key = urldecode($path[$i]);
                $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
            }
        }
        
        if ($partial) $this->setMatchedPath($matchedPath);
        
        // Add all the parts together
        $this->parts = $parts + $params;
        
        // Add in any missing parts as defaults
        return $this->parts + $this->defaults;
    }
}
?>