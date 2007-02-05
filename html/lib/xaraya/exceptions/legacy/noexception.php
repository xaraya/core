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

sys::import('xaraya.exception.legacy.exception');
class NoException extends xarException
{
    function NoException() 
    {
        $this->major = XAR_NO_EXCEPTION;
        $this->id = "NoException initialized";
        $this->title = "No Exception";
    }
}

?>
