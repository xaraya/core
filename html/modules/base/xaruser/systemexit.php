<?php

/**
 * File: $Id$
 *
 * Base System Exit function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
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
    if (!xarVarFetch('exception', 'str', $msg, NULL, XARVAR_NOT_REQUIRED)) return;
    if($CoreStack->isempty()) $CoreStack->initialize();
    $exception = new SystemException($msg);
    $exception->setID('PHP_ERROR');
    $exception->setMajor(XAR_SYSTEM_EXCEPTION);
    $CoreStack->push($exception);

    static $spinning = false;

    if ($spinning) {
        echo "Hit a reoccurring error. Here is the original error message:";
        echo "<br /><br />" . $msg;
    }
    else {
        $spinning = true;
        $text = xarErrorRender('html', "CORE");
        $pageOutput = xarTpl_renderPage($text);
        echo $pageOutput;
    }
    exit;
}
?>
