<?php

function mail_admin_new($args)
{
    return xarResponse::redirect(xarModUrl('mail','admin','view'));
}
?>