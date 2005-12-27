<?php

function foo_admin_main()
{
    if(!xarSecurityCheck('AdminFoo')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0) {
        return array();
    } else {
        xarResponseRedirect(xarModURL('foo', 'admin', 'modifyconfig'));
    }
    // success
    return true;
}
?>