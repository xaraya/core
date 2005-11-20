<?php
/**
 * Display a php error in raw html 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * This function displays a PHP error in raw html and then exits.
 * If we are here it's because the error makes it impossible to give a standard rendering
 *
 * @author Marc Lutolf
 */
function base_user_rawexit()
{
    $errorcodes = array(
                '1' => "E_ERROR",
                '2' => "E_WARNING",
                '4' => "E_PARSE",
                '8' => "E_NOTICE",
                '16' => "E_CORE_ERROR",
                '32' => "E_CORE_WARNING",
                '64' => "E_COMPILE_ERROR",
                '128' => "E_COMPILE_WARNING",
                '256' => "E_USER_ERROR",
                '512' => "E_USER_WARNING",
                '1024' => "E_USER_NOTICE"
                );
    if (isset($_GET['exception'])) $msg = $_GET['exception'];
    else $msg = "(no error message available)";
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $errorcode = $errorcodes[$code];
    }
    else $code = "(no error code available)";

    $rawmsg = "<b>Recursive Error</b><br /><br />";
    $rawmsg .= "Normal error processing has been stopped because of a recurring PHP error. <br /><br />";
    $rawmsg .= "The last registered error message is: <br /><br />";
    $rawmsg .= "Error code: " . $errorcode . "<br /><br />";
    // avoid nasties trying to post fake exceptions
    $rawmsg .= xarVarPrepHTMLDisplay($msg);
    echo $rawmsg;
    exit;
}
?>
