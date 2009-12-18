<?php
/**
 * Base Action Controller class
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage controllers
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.controllers.interfaces');

class BaseActionController extends Object implements iController
{
    private $controller;
    private $request;
    private $url = '';
    
    protected $decodearray  = array();

    public $separator = '&';    // This is the default separator between URL parameters in the default Xaraya route
    
    public function __construct(xarRequest $request=null)
    {
        $this->request = $request;
        $this->actionstring = $request->getActionString();
        $this->separator = $this->request->delimiter;
        $this->module = $this->request->getModule();
    }
        
    function run(xarRequest $request=null, xarResponse $response=null)          
    {
        $this->actionstring = $request->getActionString();
        $this->decode($request);
//        $this->chargeRequest($request, $this->decode(request));
        $response->output = xarMod::guiFunc($request->getModule(), $request->getType(), $request->getFunction(), $request->getURLParams());
    }

    function decode(xarRequest $request)        { return array(); }
    function encode(xarRequest $request)          
    {         
        $path[$request->getModuleKey()] = $request->getModule();
        $path[$request->getTypeKey()] = $request->getType();
        $path[$request->getFunctionKey()] = $request->getFunction();
//        $path = $path + $request->getURLParams();
        $path = xarURL::addParametersToPath($path, '', xarController::$delimiter, $this->separator);
        return $path;
    }

    function getController()   { return $this->controller; }
    function getRequest()      { return $this->request; }
    function getOutput()       
    { 
//        $data = $this->decode();
//        $this->decodearray = $data;
//        $this->func = $data['func'];
//        unset($data['func']);
        return $response->output;
//        return xarMod::guiFunc($this->module, $this->type, $this->func); 
    }
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
        $request->setURLParams($params);
    }
    public function getActionString(xarRequest $request)       
    { 
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL(), strlen($initialpath));
        return $actionstring;
    }
}
?>