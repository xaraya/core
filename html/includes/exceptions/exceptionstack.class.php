<?php
/**
 * File: $Id$
 *
 * Error Stack class
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

class xarExceptionStack
{
    var $stack;

    function xarExceptionStack()
    {}

    function isempty() 
    { 
        return count($this->stack) == 0; 
    }
    
    function size() 
    { 
        return count($this->stack); 
    }
    
    function peek() 
    { 
        return $this->stack[count($this->stack)-1]; 
    }
    
    function pop() 
    {
        $obj = $this->stack[count($this->stack)-1];
        array_pop($this->stack);
        return $obj;
    }
    
    function push($obj) 
    { 
        $this->stack[] = $obj;
    }
    
    function initialize() 
    { 
        $this->stack = array(new NoException());
    }
}

?>