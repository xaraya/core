<?php
/**
 * File: $Id$
 *
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

include_once "includes/exceptions/exceptionrendering.class.php";

class HTMLExceptionRendering extends ExceptionRendering
{

    function getTitle() 
    { 
        return xarVarPrepForDisplay(parent::getTitle()); 
    }
    
    function getShort() 
    {
        if (substr($this->exception->getID(),0,2) == "E_") return parent::getShort();
        else return xarVarPrepForDisplay(parent::getShort());
    }
    
    function getHint() 
    { 
        return xarVarPrepForDisplay(parent::getHint()); 
    }
    
    function getMsg() 
    { 
        return xarVarPrepForDisplay(parent::getMsg()); 
    }

}
?>