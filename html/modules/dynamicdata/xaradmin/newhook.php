<?php
/**
 * Select dynamicdata for a new item
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * select dynamicdata for a new item - hook for ('item','new','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_newhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } elseif (isset($objectid)) {
        $itemid = $objectid;
    } else {
        $itemid = 0;
    }
    $tree = xarModAPIFunc('dynamicdata','user', 'getancestors', array('moduleid' => $modid, 'itemtype' => $itemtype, 'base' => false));

    $data = "";
    foreach ($tree as $branch) {
    	if ($branch['objectid'] == 0) continue;
    	// TODO: this next line jumps over itemtypes that correspond to wrappers of native itemtypes
    	// TODO: make this more robust
    	if ($branch['itemtype'] < 1000) continue;

		$object = & Dynamic_Object_Master::getObject(array(
										   'objectid' => $branch['objectid'],
										   'moduleid' => $modid,
										   'itemtype' => $itemtype,
										   'itemid'   => $itemid));
		if (!isset($object)) return;

		// if we are in preview mode, we need to check for any preview values
		if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
		if (!empty($preview)) {
			$object->checkInput();
		}

		if (!empty($object->template)) {
			$template = $object->template;
		} else {
			$template = $object->name;
		}
		$data .= xarTplModule('dynamicdata','admin','newhook',
							array('properties' => & $object->properties),
							$template);
    }
    return $data;
}

?>
