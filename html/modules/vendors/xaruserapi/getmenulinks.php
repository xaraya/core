<?php

function foo_userapi_getmenulinks()
{

    if (xarSecurityCheck('ViewFoo',0)) {
        $menulinks[] = Array('url'   => xarModURL('foo',
                                                  'user',
                                                  'main'),
                              'title' => xarML(''),
                              'label' => xarML(''));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }
    return $menulinks;
}

?>
