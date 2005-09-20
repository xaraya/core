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

class SystemException extends xarException
{
    function SystemException($msg = '')
    {
        parent::xarException();
        $this->msg = $msg;
        if (isset($GLOBALS['xarRequest_allowShortURLs']) &&
            $GLOBALS['xarRequest_allowShortURLs'] &&
            isset($GLOBALS['xarRequest_shortURLVariables']['module'])) {
            $this->module = $GLOBALS['xarRequest_shortURLVariables']['module'];
        // Then check in $_GET
        } elseif (isset($_GET['module'])) {
            $this->module = $_GET['module'];
        // Try to fallback to $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS']['module'])) {
            $this->module = $GLOBALS['HTTP_GET_VARS']['module'];
        // Nothing found, return void
        } else {
            $this->module = '';
        }
        // load relative to the current file (e.g. for shutdown functions)
        if (!isset($core)) include(dirname(__FILE__) . "/xarayacomponents.php");
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
