<?php

function foo_adminapi_getmenulinks()
{
    if (xarSecurityCheck('AdminFoo',0)) {
        $menulinks[] = Array('url'   => xarModURL('foo',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the configuration settings'),
                              'label' => xarML('Modify Config'));
    }
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>