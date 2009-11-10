<?php

function mail_admin_new($args)
{
    return xarController::$response->redirect(xarModUrl('mail','admin','view'));
}
?>