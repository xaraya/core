<?php

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 */
function dynamicdata_user_display($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_NOT_REQUIRED)) {return;}

/*  // we could also pass along the parameters to the template, and let it retrieve the object
    // but in this case, we'd need to retrieve the object label anyway
    return array('objectid' => $objectid,
                 'modid' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid);
*/

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) return;
    $myobject->getItem();

    // Return the template variables defined in this function
    return array('object' => & $myobject);
}


?>
