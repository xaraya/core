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

    function __construct($request=null)
    {
        $this->controller = $this;
    }
    
    function getController()   { return $this->controller; }
    function getRequest()     { return $this->request; }
    function getOutput()   { return ""; }
}
?>