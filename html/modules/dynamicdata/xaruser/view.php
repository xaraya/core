<?php

/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function dynamicdata_user_view()
{
    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (!xarModAPILoad('dynamicdata','user')) return;
    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        $objectid = 0;
        $label = xarML('Dynamic Data Objects');
        $param = '';
    }
	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    $data = xarModAPIFunc('dynamicdata','user','menu');
    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['label'] = $label;

/*  // we could also retrieve the object list here, and pass that along to the template
    $numitems = 30;
    $mylist = new Dynamic_Object_List(array('objectid' => $objectid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'status'   => 1));
    $mylist->getItems(array('numitems' => $numitems,
                            'startnum' => $startnum));

    $data['object'] = & $mylist;
*/
    return $data;
}

?>