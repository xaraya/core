<?php
function mail_admin_template()
{
    // Security Check
    if(!xarSecurityCheck('AdminMail')) return;
    // Get parameters
    if (!xarVarFetch('message', 'str:1:', $message, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'str:1:', $phase, 'form', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    switch(strtolower($phase)) {
        case 'form':
        default:
            $data['body'] = xarModGetVar('mail', 'hooktemplate');
            if (empty($data['body'])){
                $message = 'enter your text';
                xarModSetVar('mail', 'hooktemplate', $message);
            }
            $data['submitlabel'] = xarML('Submit');
            $data['authid'] = xarSecGenAuthKey();
            break;
        case 'update':
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            xarModSetVar('mail', 'hooktemplate', $message);
            xarResponseRedirect(xarModURL('mail', 'admin', 'template'));
            break;
    }
    // Return the output
 return $data;
}
?>