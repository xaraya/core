<?php

// returns a list of all the ancestors of an object as an array of arrays

function &dynamicdata_userapi_getancestors($args)
{
    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    extract($args);

    if (!(isset($moduleid) && isset($itemtype)) && !isset($objectid)) {
        $msg = xarML('Wrong arguments to dynamicdata_userapi_getancestors.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $xartable =& xarDBGetTables();
	$q = new xarQuery('SELECT',$xartable['dynamic_objects']);
	$q->addfields(array('xar_object_id AS objectid','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
    if (isset($objectid)) {
		$q->eq('xar_object_id',$objectid);
		if (!$q->run()) return;
		$result = $q->row();
		if ($result == array()) {
			$msg = xarML('Bad objectid for dynamicdata_userapi_getancestors.');
			xarErrorSet(XAR_SYSTEM_EXCEPTION,
						'BAD_PARAM',
						 new SystemException($msg));
			return false;
		}
		$moduleid = $result['moduleid'];
		$itemtype = $result['itemtype'];
   } else {
	    $q->eq('xar_object_moduleid',$moduleid);
	    $q->eq('xar_object_itemtype',$itemtype);
		if (!$q->run()) return;
		$result = $q->row();
		if ($result == array()) {
			$msg = xarML('Bad moduleid or itemtype for dynamicdata_userapi_getancestors.');
			xarErrorSet(XAR_SYSTEM_EXCEPTION,
						'BAD_PARAM',
						 new SystemException($msg));
			return false;
		}
		$objectid = $result['objectid'];
   }

    $top = isset($top) ? $top : true;
    $base = isset($base) ? $base : true;

    if ($top) {
    	$ancestors[] = xarModAPIFunc('dynamicdata','user', 'getobjectinfo', array('objectid' => $objectid));
    } else {
    	$ancestors = array();
    }

    // Get all the objects at once
    $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
    $q->addfields(array('xar_object_id AS objectid','xar_object_name AS objectname','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
    $q->eq('xar_object_moduleid',$moduleid);
    if (!$q->run()) return;

    // put in itemtype as key for easier manipulation
    foreach($q->output() as $row) $objects [$row['itemtype']] = array('objectid' => $row['objectid'],'objectname' => $row['objectname'], 'moduleid' => $row['moduleid'], 'parent' => $row['parent']);

    $parentitemtype = $result['parent'];
    for(;;) {
    	$done = false;
    	if ($parentitemtype == 0) {
    		$done = true;
    		$itemtype = $parentitemtype;
	    	$id = $moduleid;
	    	$mod = xarModGetInfo($id);
	    	$name = $mod['name'];
    	} else {
			$parent = $objects[$parentitemtype];
    		$moduleid = $parent['moduleid'];
	    	$id = $parent['objectid'];
    		$itemtype = $parentitemtype;
	    	$name = $parent['objectname'];
	    	$parentitemtype = $parent['parent'];
    	}
    	if (!$done || $base) $ancestors[] = array('objectid' => $id, 'itemtype' => $itemtype, 'name' => $name);
    	if ($done) break;
    }
    return array_reverse($ancestors, true);

}

?>