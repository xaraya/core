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

sys::import('exceptions.legacy.exception');
class SystemException extends xarException
{
    function SystemException($msg = '')
    {
        parent::xarException();
        $this->msg = $msg;
        if (isset(xarRequest::$allowShortURLs) &&
            xarRequest::$allowShortURLs &&
            isset(xarRequest::$shortURLVariables['module'])) {
            $this->module = xarRequest::$shortURLVariables['module'];
        // Then check in $_GET
        } elseif (isset($_GET['module'])) {
            $this->module = $_GET['module'];
        // Nothing found, return void
        } else {
            $this->module = '';
        }
        // load relative to the current file (e.g. for shutdown functions)
        if (!isset($core)) sys::import('exceptions.xarayacomponents');
        foreach (xarComponents::$core as $corecomponent) {
            if ($corecomponent['name'] == $this->module) {
                $this->component = $corecomponent['fullname'];
                $this->product = "App - Core";
                return;
            }
        }
        foreach (xarComponents::$apps as $appscomponent) {
            if ($appscomponent['name'] == $this->module) {
                $this->component = $appscomponent['fullname'];
                $this->product = "App - Modules";
                return;
            }
        }
    }
}

?>
