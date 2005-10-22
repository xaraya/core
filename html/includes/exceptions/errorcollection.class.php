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

include_once "includes/exceptions/exception.class.php";

/**
 * ErrorCollection
 *
 * it has to be raised as an exception
 * it's a container of error/exceptions
 * for now it's used only by the PHP error handler bridge
 * @package exceptions
 */

class ErrorCollection extends xarException
{
    var $exceptions = array();

    function ErrorCollection() 
    {
        $this->title = "PHP Error";
//        $this->msg = xarML("Default message");
    }

    function toString()
    {
        if (count($this->exceptions) == 0) {
            $text = "Empty error stack";
        }
        else {
            $text = "";
            foreach($this->exceptions as $exc) {
    //            $text .= "Exception $exc[id]\n";
                if (method_exists($exc['value'], 'toString')) {
                    $text .= $exc['value']->toString();
                    $text .= "\n";
                }
            }
        }
        return $text;
    }

    function toHTML()
    {
        if (count($this->exceptions) == 0) {
            $text = "Empty error stack";
        }
        else {
            $text = "";
            foreach($this->exceptions as $exc) {
    //            $text .= "Exception identifier: <b>$exc[id]</b><br />";
                if (method_exists($exc['value'], 'toHTML')) {
                    $text .= $exc['value']->toHTML();
                    $text .= '<br />';
                }
            }
        }
        return $text;
    }

}

?>
