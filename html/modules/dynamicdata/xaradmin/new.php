<?php

/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $preview) = xarVarCleanFromInput('objectid',
                                          'modid',
                                          'itemtype',
                                          'itemid',
                                          'preview');

    extract($args);

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($itemid)) {
        $itemid = 0;
    }

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
	if(!xarSecurityCheck('AddDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    $data['object'] = new Dynamic_Object(array('objectid' => $objectid,
                                               'moduleid' => $modid,
                                               'itemtype' => $itemtype,
                                               'itemid'   => $itemid));

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $item = array();
    $item['module'] = 'dynamicdata';
    $hooks = xarModCallHooks('item','new','',$item);
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