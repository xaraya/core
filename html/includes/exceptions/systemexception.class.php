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


include_once "includes/exceptions/exception.class.php";

class SystemException extends Exception
{
    function SystemException($msg = '') {
        $this->msg = $msg;
        $info = xarRequestGetInfo();
        $this->module = $info[0];
        include("xarayacomponents.php");
        foreach ($core as $corecomponent) {
            if ($corecomponent['name'] == $this->module) {
                $this->component = $corecomponent['fullname'];
                $this->product = "App - Core";
                return;
            }
        }
        foreach ($apps as $appscomponent) {
            if ($appscomponent['name'] == $this->module) {
                $this->component = $appscomponent['fullname'];
                $this->product = "App - Modules";
                return;
            }
        }
    }
}

?>