<?php

/**
 * File: $Id$
 *
 * Base System Exit function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * This function renders a core exception and then exits
 *
 * @subpackage base
 * @author Marc Lutolf
 */



function base_user_systemexit()
{
    global $CoreStack;
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
    if (!xarVarFetch('exception', 'str', $msg, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('code', 'str', $code, NULL, XARVAR_NOT_REQUIRED)) return;
    if($CoreStack->isempty()) $CoreStack->initialize();
    $exception = new SystemException($msg);
    $exception->setID($errorcodes[$code]);
    $exception->setMajor(XAR_SYSTEM_EXCEPTION);
    $CoreStack->push($exception);

    static $spinning = false;

    if ($spinning) {
        echo "Hit a reoccurring error. Here is the original error message:";
        echo "<br /><br />" . $msg;
    }
    else {
        $spinning = true;
        $text = xarErrorRender('template', "CORE");
        $pageOutput = xarTpl_renderPage($text);
        echo $pageOutput;
    }
    exit;
}
?>
