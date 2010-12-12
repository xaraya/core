<?php
/**
 * Manage definition of instances for privileges
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Manage definition of instances for privileges (unfinished)
 */
function dynamicdata_admin_privileges($args)
{ 
    // Security
    if (!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (!xarVarFetch('objectid', 'id' , $objectid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('moduleid', 'str', $moduleid, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric or modulename
    if (!xarVarFetch('itemtype', 'str', $itemtype, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric 
    if (!xarVarFetch('itemid', 'str', $itemid, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric  
    if (!xarVarFetch('apply', 'str' , $apply , false, XARVAR_NOT_REQUIRED)) return; // boolean?
    if (!xarVarFetch('extpid', 'str', $extpid, '', XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric ?
    if (!xarVarFetch('extname', 'str', $extname, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extrealm', 'str', $extrealm, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extmodule','str', $extmodule, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extcomponent', 'enum:All:Item:Field:Type', $extcomponent)) return; // FIXME: is 'Type' needed?
    if (!xarVarFetch('extinstance', 'str:1', $extinstance, '', XARVAR_NOT_REQUIRED)) return; // somthing:somthing:somthing or empty
    if (!xarVarFetch('extlevel', 'str:1', $extlevel)) return;

// TODO: combine 'Item' and 'Type' instances someday ?

    if (!empty($extinstance)) {
        $parts = explode(':',$extinstance);
        if ($extcomponent == 'Item') {
            if (count($parts) > 0 && !empty($parts[0])) $moduleid = $parts[0];
            if (count($parts) > 1 && !empty($parts[1])) $itemtype = $parts[1];
            if (count($parts) > 2 && !empty($parts[2])) $itemid = $parts[2];
        } else {
        }
    }

    if ($extcomponent == 'Item') {

        if (empty($moduleid) || $moduleid == 'All') {
            $moduleid = 0;
        } elseif (!is_numeric($moduleid)) { // for pre-wizard instances
            $module_id = xarMod::getRegID($moduleid);
            if (!empty($module_id)) {
                $moduleid = $module_id;
            } else {
                $moduleid = 0;
            }
        }
        if (empty($itemtype) || $itemtype == 'All' || !is_numeric($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid) || $itemid == 'All' || !is_numeric($itemid)) {
            $itemid = 0;
        }

        // define the new instance
        $newinstance = array();
        $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
        $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
        $newinstance[] = empty($itemid) ? 'All' : $itemid;

    } else {

        // define the new instance
        $newinstance = array();

    }

    if (!empty($apply)) {
        // create/update the privilege
        $pid = xarReturnPrivilege($extpid,$extname,$extrealm,$extmodule,$extcomponent,$newinstance,$extlevel);
        if (empty($pid)) {
            return; // throw back
        }

        // redirect to the privilege
        xarResponse::redirect(xarModURL('privileges', 'admin', 'modifyprivilege',
                                        array('id' => $pid)));
        return true;
    }

    // Get objects
    $objects = xarMod::apiFunc('dynamicdata','user','getobjects');

    // TODO: use object list instead of (or in addition to) module + itemtype

    // Get module list
    $modlist = array();
    // Get a list of all modules - we just want their IDs
    $all_modules = xarMod::apiFunc('modules', 'admin', 'getlist');
    $all_module_ids = array();
    foreach($all_modules as $this_module) {
        $all_module_ids[] = $this_module['regid'];
    }
    foreach ($objects as $id => $object) {
        $module_id = $object['moduleid'];
        // Check whether the module exists before trying to fetch the details.
        if (in_array($module_id, $all_module_ids)) {
            $modinfo = xarMod::getInfo($module_id);
            $modlist[$module_id] = $modinfo['displayname'];
        }
    }

    if ($extcomponent == 'Item') {
        if (!empty($itemid)) {
            $numitems = xarML('probably');
        } elseif (!empty($objectid) || !empty($moduleid)) {
            $numitems = xarMod::apiFunc('dynamicdata','user','countitems',
                                      array('objectid' => $objectid,
                                            'moduleid' => $moduleid,
                                            'itemtype' => $itemtype));
            if (empty($numitems)) {
                $numitems = 0;
            }
        } else {
            $numitems = xarML('probably');
        }

    } else { // 'Type'

        $numitems = xarML('probably');

    }

    $data = array(
                  'objectid'     => $objectid,
                  'moduleid'     => $moduleid,
                  'itemtype'     => $itemtype,
                  'itemid'       => $itemid,
                  'objectlist'   => $objects,
                  'modlist'      => $modlist,
                  'numitems'     => $numitems,
                  'extpid'       => $extpid,
                  'extname'      => $extname,
                  'extrealm'     => $extrealm,
                  'extmodule'    => $extmodule,
                  'extcomponent' => $extcomponent,
                  'extlevel'     => $extlevel,
                  'extinstance'  => xarVarPrepForDisplay(join(':',$newinstance)),
                 );

    $data['refreshlabel'] = xarML('Refresh');
    $data['applylabel'] = xarML('Finish and Apply to Privilege');

    return $data;
}

?>