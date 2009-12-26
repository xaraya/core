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

class BaseActionController extends Object
{
    private $controller;
    private $request;
    
    public function __construct(xarRequest $request=null)
    {
        $this->request = $request;
        $this->actionstring = $request->getActionString();
        $this->module = $this->request->getModule();
    }
        
    function run(xarRequest $request=null, xarResponse $response=null)          
    {
        $this->actionstring = $request->getActionString();
        $args = $this->decode();
        $this->chargeRequest($request, $args);
        $_GET = $_GET + $args + $request->getURLParams();
        if ($request->getModule() == 'object') {
            sys::import('xaraya.objects');
            $response->output = xarObject::guiMethod($request->getType(), $request->getFunction());
        } else {
            $response->output = xarMod::guiFunc($request->getModule(), $request->getType(), $request->getFunction(), $request->getURLParams());
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
        $request->setURLParams($params);
    }
    
}
?>