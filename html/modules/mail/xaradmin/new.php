<?php

function mail_admin_new($args)
{
    return xarResponseRedirect(xarModUrl('mail','admin','view'));
}
?>