<?php

/**
 * view items
 */
function dynamicdata_admin_view($args)
{
    extract($args);

    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_NOT_REQUIRED)) {return;}

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        return;
    }

    $data = xarModAPIFunc('dynamicdata','admin','menu');

/*
    $mylist = new Dynamic_Object_List(array('objectid' => $itemid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype));
    $data['mylist'] = & $mylist;
*/

    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['label'] = $label;
    $data['sort']=$sort;

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
// Security Check
	if(!xarSecurityCheck('EditDynamicData')) return;

    // show other modules
    $data['modlist'] = array();
    if ($objectid == 1) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        $seenmod = array();
        foreach ($objects as $object) {
            $seenmod[$object['moduleid']] = 1;
        }

        $modList = xarModGetList(array(),NULL,NULL,'category/name');
        $oldcat = '';
        for ($i = 0; $i < count($modList); $i++) {
            if (!empty($seenmod[$modList[$i]['regid']])) {
                continue;
            }
            if ($oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = $modList[$i]['category'];
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
			if(xarSecurityCheck('AdminDynamicDataItem',0,'Item',$modList[$i]['regid'].':All:All')) {
                $modList[$i]['link'] = xarModURL('dynamicdata','admin','modifyprop',
                                                  array('modid' => $modList[$i]['regid']));
            } else {
                $modList[$i]['link'] = '';
            }
            $data['modlist'][] = $modList[$i];
        }
    }

    // Return the template variables defined in this function
    return $data;
}

?>
