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

    public $delimiter = '/';
    public $module    = 'base';
    public $type      = 'user';
    public $func      = 'main';
    
    function __construct($request=null)
    {
//        if (empty($request)) $request = new xarRequest();
        $this->request = $request;
        $this->actionstring = $this->request->getActionString();
        $this->assemble();
    }
    
    function run(Array $data=array()) 
    { 
        $url = 'module=' . $this->module;
        $url .= 'type=' . $this->type;
        foreach ($data as $key => $value) $url .= '&' . $key . '=' . $value;
        echo $url;
        
        return true; 
    }
    
    function assemble()        { return array(); }
    function getController()   { return $this->controller; }
    function getRequest()      { return $this->request; }
    function getOutput()       { return ""; }
    function firstToken()      { return strtok($this->actionstring, $this->delimiter); }
    function nextToken()       { return strtok($this->delimiter); }
}
?>