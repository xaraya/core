<?php

function mail_admin_new($args)
{
    return xarResponse::Redirect(xarModUrl('mail','admin','view'));
}
?>