<?php
/* 
 * Queue status management
 *
 */
function mail_admin_qstatus($args)
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return;

    $data = array();

    // Do we have the master ?
    if(!$qdefInfo = xarModApiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarResponseRedirect(xarModUrl('mail','admin','view'));
        return true;
    }

    return $data;
}
?>
