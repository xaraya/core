<?php

// returns a list of all the ancestors of an object as an array of arrays

function &dynamicdata_userapi_getancestors($args)
{
    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    extract($args);

    if (!(isset($moduleid) && isset($itemtype)) && !isset($objectid)) {
        $msg = xarML('Wrong arguments to dynamicdata_userapi_getancestors.');
        throw new BadParameterException(array(),$msg);
    }

    $top = isset($top) ? $top : true;
    $base = isset($base) ? $base : true;
    $ancestors = array();

// -------------- Get the info of this object
    $xartable =& xarDBGetTables();
    if (isset($objectid)) {
// -------------- We have an objectid - get the moduleid and itemtype
        $topobject = xarModAPIFunc('dynamicdata','user', 'getobjectinfo', array('objectid' => $objectid));
        $moduleid = $topobject['moduleid'];
        $itemtype = $topobject['itemtype'];
   } else {
// -------------- We have a moduleid and itemtype - get the objectid
        $topobject = xarModAPIFunc('dynamicdata','user', 'getobjectinfo', array('moduleid' => $moduleid, 'itemtype' => $itemtype));
        if (empty($topobject)) {
            if ($base) {
                $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $moduleid));
                $info = array('objectid' => 0, 'itemtype' => $itemtype, 'name' => xarModGetNameFromID($moduleid));
                $ancestors[] = $info;
                return $ancestors;
            }
            return $ancestors;
        }
        $objectid = $topobject['objectid'];
   }

// -------------- Include the last descendant (this object) or not
    if ($top) {
        $ancestors[] = xarModAPIFunc('dynamicdata','user', 'getobjectinfo', array('objectid' => $objectid));
    }

// -------------- Get all the dynamic objects at once
    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
    $q->addfields(array('xar_object_id AS objectid','xar_object_name AS objectname','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
    $q->eq('xar_object_moduleid',$moduleid);
    if (!$q->run()) return;

// -------------- Put in itemtype as key for easier manipulation
    foreach($q->output() as $row) $objects [$row['itemtype']] = array('objectid' => $row['objectid'],'objectname' => $row['objectname'], 'moduleid' => $row['moduleid'], 'itemtype' => $row['itemtype'], 'parent' => $row['parent']);

// -------------- Cycle through each ancestor
    $parentitemtype = $topobject['parent'];
    for(;;) {
        $done = false;

        if ($parentitemtype >= 1000) {

// -------------- This is a DD descendent object. add it to the ancestor array
            $thisobject     = $objects[$parentitemtype];
            $moduleid       = $thisobject['moduleid'];
            $objectid       = $thisobject['objectid'];
            $itemtype       = $thisobject['itemtype'];
            $name           = $thisobject['objectname'];
            $parentitemtype = $thisobject['parent'];
            $ancestors[] = array('objectid' => $objectid, 'itemtype' => $itemtype, 'name' => $name, 'moduleid' => $moduleid);
        } else {

// -------------- This is a native itemtype. get ready to quit
            $done = true;
            $itemtype = $parentitemtype;
            if ($itemtype) {
                if ($info=xarModAPIFunc('dynamicdata','user', 'getobjectinfo', array('moduleid' => $moduleid, 'itemtype' => $itemtype))) {

    // -------------- A DD wrapper object exists, add it to the ancestor array
                    if ($base) $ancestors[] = array('objectid' => $info['objectid'], 'itemtype' => $itemtype, 'name' => $info['name'], 'moduleid' => $moduleid);
                } else {

    // -------------- No DD wrapper object
    // -------------- This must be a native itemtype of some module - add it to the ancestor array if requested
                        $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $moduleid));
                        $name = strtolower($types[$itemtype]['label']);
                        if ($base) {$ancestors[] = array('objectid' => 0, 'itemtype' => $itemtype, 'name' => $name, 'moduleid' => $moduleid);}
                }
            } else {

// -------------- Itemtype = 0. We're already at the bottom - do nothing
            }
        }
        if ($done) break;
    }
    $ancestors = array_reverse($ancestors, true);
    return $ancestors;

}

?>