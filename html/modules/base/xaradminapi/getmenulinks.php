<?php

/**
 * Pass individual menu items to the admin menu
 *
 * @author the Example module development team
 * @return array containing the menulinks for the admin menu items.
 */
function base_adminapi_getmenulinks()
{
     // Security Check
    if (xarSecurityCheck('AdminBase',0)) {

        $menuLinks[] = array('url'   => xarModURL('base','admin','sysinfo'),
                             'title' => xarML('View Your PHP Configuration'),
                             'label' => xarML('System'));
        $menuLinks[] = array('url'   => xarModURL('base','admin','modifyconfig'),
                             'title' => xarML('Modify Base Configuration Values'),
                             'label' => xarML('Modify Config'));
    }
    if (empty($menuLinks)){
        $menuLinks = '';
    }

    return $menuLinks;
}
?>
