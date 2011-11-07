<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function categories_adminapi_getmenulinks()
{
    return xarMod::apiFunc('base','admin','menuarray',array('module' => 'categories'));

}


?>
