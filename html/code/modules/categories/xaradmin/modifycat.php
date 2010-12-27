<?php

/**
 * udpate item from categories_admin_modify
 */
function categories_admin_modifycat()
{
    if (!xarVarFetch('creating', 'bool', $creating, true, XARVAR_NOT_REQUIRED)) {return;}
    if ($creating) {
        return xarMod::guiFunc('categories','admin','new');
    } else {
        return xarMod::guiFunc('categories','admin','modify');
    }
}

?>
