<?php
/**
 * Base Action Controller class
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

sys::import('xaraya.mapper.controllers.interfaces');

class BaseActionController extends Object
{
    private $controller;
    private $request;
    
    public $module;
    public $modulealias;
    
    public function __construct(xarRequest $request=null)
    {
        $this->request = $request;
        $this->actionstring = $request->getActionString();
        $this->module = $this->request->getModule();
        $this->modulealias = $this->request->getModuleAlias();
    }
        
    function run(xarRequest $request=null, xarResponse $response=null)          
    {
        // Get the part of the URL we will tokenize and decode
        $this->actionstring = $request->getActionString();
        // Add the results of decoding to the params we already got when the request was created
        $args = $this->decode() + $request->getFunctionArgs();
        // Allocate those params we can to module/type/function and store the rest as FunctionArgs in the request
        $this->chargeRequest($request, $args);
        // Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVarFetch
        $_GET = $_GET + $args;
        // Now get the output
        if ($request->getModule() == 'object') {
            sys::import('xaraya.objects');
            $response->output = xarObject::guiMethod($request->getType(), $request->getFunction(), $request->getFunctionArgs());
        } else {
            $response->output = xarMod::guiFunc($request->getModule(), $request->getType(), $request->getFunction(), $request->getFunctionArgs());
        }
    }

    function getController()   { return $this->controller; }
    function getRequest()      { return $this->request; }
    function getOutput()       { return $response->output;}
    function firstToken()      { return strtok($this->actionstring, $this->separator); }
    function nextToken()       { return strtok($this->separator); }
    
    function chargeRequest(xarRequest $request, Array $params=array())       
    { 
        if (isset($params['module'])) {
            $request->setModule($params['module']);
            unset($params['module']);
        }
        if (isset($params['type'])) {
            $request->setType($params['type']);
            unset($params['type']);
        }
        if (isset($params['func'])) {
            $request->setFunction($params['func']);
            unset($params['func']);
        }
        if (isset($params['object'])) {
            $request->setType($params['object']);
            unset($params['object']);
        }
        if (isset($params['method'])) {
            $request->setFunction($params['method']);
            unset($params['method']);
        }
        $request->setFunctionArgs($params);
    }
    
}
?>