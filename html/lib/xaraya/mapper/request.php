<?php
/**
 * Request class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarRequest extends Object
{
    protected $url;
    protected $actionstring;
    protected $dispatched = false;
    protected $modulekey = 'module';
    protected $typekey   = 'type';
    protected $funckey   = 'func';
    protected $route     = 'default';

    public $module    = 'base';
    public $modulealias = '';
    public $type      = 'user';
    public $func      = 'main';
    public $funcargs  = array();
    public $object    = 'objects';
    public $method    = 'view';
    
    public $defaultRequestInfo = array();
    public $isObjectURL        = false;

    public $entryPoint;
    public $separator    = '&';
    
    private $isAjax   = null;
    
    function __construct($url=null)
    {
        // Make this load lazily
        $this->setModule(xarModVars::get('modules', 'defaultmodule'));
        $this->setType(xarModVars::get('modules', 'defaultmoduletype'));
        $this->setFunction(xarModVars::get('modules', 'defaultmodulefunction'));

        $this->entryPoint = xarController::$entryPoint;
        $this->setURL($url);
    }

    function setURL($url=null)
    {
        if (null != $url) {
            // A URL was passed
            if (is_array($url)) {
                // This is an array representing a traditional Xaraya URL array
                if (!empty($url['module'])) {
                    // Resolve if this is an alias for some other module
                    $this->setModule(xarModAlias::resolve($url['module']));
                    if ($this->getModule() != $url['module']) $this->setModuleAlias($url['module']);
                    unset($url['module']);
                }
                if (!empty($url['type'])) {
                    $this->setType($url['type']);
                    unset($url['type']);
                }
                if (!empty($url['func'])) {
                    $this->setFunction($url['func']);
                    unset($url['func']);
                }
                
                // Rename the array so we can use the code at the end
                $params = $url;
                
                // CHECKME: how should this URL be stored?
                
            } else {
                // This is a string representing a URL
                $url = preg_replace('/&amp;/','&',$url);
                $params = xarController::parseQuery($url);
                if (!empty($params['module'])) $this->setModule($params['module']);
                if (!empty($params['type'])) $this->setType($params['type']);
                if (!empty($params['func'])) $this->setFunction($params['func']);
                
                // Store the URL
                $this->url = $url;
                
            }
        } else {
            // CHECKME: are these next lines needed?
            // Try and get it from the current request path
            $url = xarServer::getCurrentURL();
            $params = $_GET;

            // We now have a URL. Set it.
            $this->url = $url;
        
            // See if this is an object call; easiest to start like this 
            xarVarFetch('object', 'regexp:/^[a-z][a-z_0-9]*$/', $objectName, NULL, XARVAR_NOT_REQUIRED);
            // Found a module object name
            if (null != $objectName) {
                $this->setModule('object');
                $this->setType($objectName);
                $this->setFunction($this->method);
            } else {
                // Try and get the module the traditional Xaraya way
                xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);

                // Else assume a form of short urls. The module name or the object keyword will be the first item
                if (null == $modName) {
                    $path = substr($url,strlen(xarServer::getBaseURL() . $this->entryPoint . xarController::$delimiter));
                    $tokens = explode('/', $path);
                    $modName = array_shift($tokens);
                    
                    // This is an object call
                    if ($modName == 'object') {
                        $this->setModule('object');
                        $this->setType(array_shift($tokens));
                        $this->setFunction($this->method);
                    
                    // This is a module name
                    } else {
                        // Resolve if this is an alias for some other module
                        if (!empty($modName)) $this->setModule(xarModAlias::resolve($modName));
                        if ($this->getModule() != $modName) $this->setModuleAlias($modName);
                    }
                } else {
                    // Resolve if this is an alias for some other module
                    if (!empty($modName)) $this->setModule(xarModAlias::resolve($modName));
                    if ($this->getModule() != $modName) $this->setModuleAlias($modName);
                }

            }
        }
        // Finally get the query parameters
        // Module, type, func, object and method are reserved names, so remove them from the array
        unset($params['module']);
        unset($params['type']);
        unset($params['func']);
        unset($params['object']);
        unset($params['method']);
        $this->setFunctionArgs($params);
        // At this point the request has assembled the module or object it belongs to and any query parameters.
        // What is still to be defined by routing are the type (for modules) and function/function arguments or method (for objects).            
    }
    
    /**
     * Gets request info for current page or a given url.
     *
     * Example of short URL support :
     *
     * index.php/<module>/<something translated in xaruserapi.php of that module>, or
     * index.php/<module>/admin/<something translated in xaradminapi.php>
     *
     * We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
     * into something the module can work with for the input variables.
     * On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
     * that is called automatically by xarModURL().
     *
     * Short URLs are enabled/disabled globally based on a base configuration
     * setting, and can be disabled per module via its admin configuration
     *
     * TODO: evaluate and improve this, obviously :-)
     * + check security impact of people combining PATH_INFO with func/type param
     *
     * @return array requested module, type and func
     * @todo <marco> Do we need to do a preg_match on $params[1] here?
     * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
     * @todo <marco> Investigate this aliases thing before to integrate and promote it!
     */
    public function getInfo($url='')
    {
        static $currentRequestInfo = NULL;
        static $loopHole = NULL;
        if (is_array($currentRequestInfo) && empty($url)) {
            return $currentRequestInfo;
        } elseif (is_array($loopHole)) {
            // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
            //        because they request the current module too at the moment - unnecessary ?
            xarLogMessage('Avoiding loop in xarController::$request->getInfo()');
            return $loopHole;
        }
        // Get variables
        if (empty($url)) {
            $info = array(
                $this->getModule(),
                $this->getType(),
                $this->getFunction(),
            );
            return $info;
        } else {
            $params = xarController::parseQuery($url);
            if (!empty($params)) {
                sys::import('xaraya.validations');
                $regex = ValueValidations::get('regexp');
            }
            if (isset($params['module'])) {
                $isvalid =  $regex->validate($params['module'], array('/^[a-z][a-z_0-9]*$/'));
                $modName = $isvalid ? $params['module'] : null;
            } else {
                $modName = null;
            }
            if (isset($params['type'])) {
                $isvalid =  $regex->validate($params['type'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
                $modType = $isvalid ? $params['type'] : 'user';
            } else {
                $modType = 'user';
            }
            if (isset($params['func'])) {
                $isvalid =  $regex->validate($params['func'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
                $funcName = $isvalid ? $params['func'] : 'main';
            } else {
                $funcName = 'main';
            }
        }

        if (!empty($modName)) {
            // Cache values into info static var
            $requestInfo = array($modName, $modType, $funcName);
        } else {
            // Check if we have an object to work with for object URLs
            xarVarFetch('object', 'regexp:/^[a-zA-Z0-9_-]+$/', $objectName, NULL, XARVAR_NOT_REQUIRED);
            if (!empty($objectName)) {
                // Check if we have a method to work with for object URLs
                xarVarFetch('method', 'regexp:/^[a-zA-Z0-9_-]+$/', $methodName, NULL, XARVAR_NOT_REQUIRED);
                // Specify 'dynamicdata' as module for xarTpl_* functions etc.
                $requestInfo = array('object', $objectName, $methodName);
                if (empty($url)) {
                    $this->isObjectURL = true;
                }
            } else {
                // If $modName is still empty we use the default module/type/func to be loaded in that such case
                if (empty($this->defaultRequestInfo)) {
                    $this->defaultRequestInfo = array(xarModVars::get('modules', 'defaultmodule'),
                                                      xarModVars::get('modules', 'defaultmoduletype'),
                                                      xarModVars::get('modules', 'defaultmodulefunction'));
                }
                $requestInfo = $this->defaultRequestInfo;
            }
        }
        // Save the current info in case we call this function again
        if (empty($url)) $currentRequestInfo = $requestInfo;
        
        return $requestInfo;
    }
    
    /**
     * Check to see if this request is an object URL
     *
     * 
     * @return boolean true if object URL, false if not
     */
    function isObjectURL() { return $this->isObjectURL; }

    function getProtocol()       { return xarServer::getProtocol(); }
    function getHost()           { return xarServer::getHost(); }
    function getModuleKey()      { return $this->modulekey; }
    function getTypeKey()        { return $this->typekey; }
    function getFunctionKey()    { return $this->funckey; }
    function getModule()         { return $this->module; }
    function getModuleAlias()    { return $this->modulealias; }
    function getType()           { return $this->type; }
    function getFunction()       { return $this->func; }
    function getObject()         { return $this->object; }
    function getMethod()         { return $this->method; }
    function getActionString()   { return $this->actionstring; }
    function getFunctionArgs()   { return $this->funcargs; }
    function getURL()            { return $this->url; }
    function getRoute()          { return $this->route; }

    function setModule($p)               { $this->module = $p; }
    function setModuleAlias($p)          { $this->modulealias = $p; }
    function setType($p)                 { $this->type = $p; }
    function setFunction($p)             { $this->func = $p; }
    function setObject($p)               { $this->object = $p; }
    function setMethod($p)               { $this->method = $p; }
    function setRoute($r)                { $this->route = $r; }
    function setActionString($p)         { $this->actionstring = $p; }
    function setFunctionArgs($p=array()) { $this->funcargs = $p; }

    public function isDispatched()
    {
        return $this->dispatched;
    }

    public function setDispatched($flag=true)
    {
        $this->dispatched = $flag ? true : false;
        return true;
    }

    public function isAJAX()
    {
        if (!isset($this->isAjax)) {
            $xhp = xarServer::getVar('HTTP_X_REQUESTED_WITH');
            if (isset($xhp) && (strtolower($xhp) === 'xmlhttprequest') && xarConfigVars::get(null, 'Site.Core.AllowAJAX')) {
                $this->isAjax = true;
            } else {
                $this->isAjax = false;
            }
        }
        return $this->isAjax;
    }

    public function exitAjax()
    {
        if ($this->isAjax()) exit;
    }

    public function msgAjax($msg)
    {
        if ($this->isAjax()) {
            if (is_array($msg)) {
                 $data = array('message' => $msg);
                 $output = xarTpl::includeTemplate('theme','','user-message',$data);
                 echo $output;
            } else {
                echo $msg;
            }
            exit;
        }
    }
}
?>