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

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}

/*  // we could also pass along the parameters to the template, and let it retrieve the object
    // but in this case, we'd need to retrieve the object label anyway
    return array('objectid' => $objectid,
                 'modid' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid);
*/

    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData')) return;
    }

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) return;
    $myobject->getItem();

    $data = array();
    $data['object'] =& $myobject;

    $modinfo = xarModGetInfo($myobject->moduleid);
    $item = array();
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $itemtype;
    $item['returnurl'] = xarModURL('dynamicdata','user','display',
                                   array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $hooks = xarModCallHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);
    if (empty($hooks)) {
        $data['hooks'] = '';
    } elseif (is_array($hooks)) {
        $data['hooks'] = join('',$hooks);
    } else {
        $data['hooks'] = $hooks;
    }

    // Return the template variables defined in this function
    return $data;
}


?>
