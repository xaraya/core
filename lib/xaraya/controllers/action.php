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

class ActionController extends Object
{
    private $controller;
    private $request;
    private $actionstring = '';
    
    protected $decodearray  = array();

    public $delimiter = '/';
    public $module    = 'base';
    public $type      = 'user';
    public $func      = 'main';
    
    function __construct(Object $request=null)
    {
        $this->request = $request;
        $this->actionstring = $this->request->getActionString();
        $this->delimiter = $this->request->delimiter;
        $this->module = $this->request->module;
    }
    
/*    function run() 
    { 
        $url = 'module=' . $this->module;
        $url .= '&type=' . $this->type;
        foreach ($this->decodearray as $key => $value) $url .= '&' . $key . '=' . $value;
        
        return true; 
    }*/
    
    function decode()        { return array(); }
    function encode($request)          
    {         
        $data['path'][] = $request->module;
        $data['path'][] = $request->type;
        $data['path'][] = $request->func;
        $data['path'] = array_merge($data['path'], $request->shortURLVariables);
        $encoded_path = $this->delimiter . implode($this->delimiter,$data['path']);
        return $encoded_path;
    }

    function getController()   { return $this->controller; }
    function getRequest()      { return $this->request; }
    function getOutput()       
    { 
        $data = $this->decode();
        $this->decodearray = $data;
        $this->func = $data['func'];
        unset($data['func']);
        return xarMod::guiFunc($this->module, $this->type, $this->func); 
    }
    function firstToken()      { return strtok($this->actionstring, $this->delimiter); }
    function nextToken()       { return strtok($this->delimiter); }
}
?>