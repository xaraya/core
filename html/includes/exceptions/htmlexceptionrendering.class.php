<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

if (!class_exists('ExceptionRendering')) {
    include_once(dirname(__FILE__) . "/exceptionrendering.class.php");
}

class HTMLExceptionRendering extends ExceptionRendering
{

    function getTitle() 
    { 
        return nl2br(htmlspecialchars(parent::getTitle())); 
    }
    
    function getShort() 
    {
        if (substr($this->exception->getID(),0,2) == "E_") return parent::getShort();
        else return nl2br(htmlspecialchars(parent::getShort()));
    }
    
    function getHint() 
    { 
        return nl2br(htmlspecialchars(parent::getHint())); 
    }
    
    function getMsg() 
    { 
        return nl2br(htmlspecialchars(parent::getMsg())); 
    }

}
?>
