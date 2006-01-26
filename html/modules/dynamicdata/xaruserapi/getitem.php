<?php
/**
 * Get all data fields for an item
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get all data fields (dynamic or static) for an item
 * (identified by module + item type + item id or table + item id)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get or
 * @param $args['modid'] module id of the item fields to get +
 * @param $args['itemtype'] item type of the item fields to get, or
 * @param $args['table'] database table to turn into an object
 * @param $args['itemid'] item id of the item fields to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['join'] join a module table to the dynamic object (if it extends the table)
 * @param $args['getobject'] flag indicating if you want to get the whole object back
 * @param $args['preview'] flag indicating if you're previewing an item
 * @returns array
 * @return array of (name => value), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitem($args)
{
    extract($args);

    // Because this function returns a reference, the return statements
    // need an explicit var to return in case of a null return
    // we define that here. Both the ref return here and the null return in
    // other functions should be investigated.
    $nullreturn = NULL;

    if (empty($modid) && empty($moduleid)) {
        if (empty($module)) {
            $modname = xarModGetName();
        } else {
            $modname = $module;
        }
        if (is_numeric($modname)) {
            $modid = $modname;
        } else {
            $modid = xarModGetIDFromName($modname);
        }
    } elseif (empty($modid)) {
        $modid = $moduleid;
    }
    $modinfo = xarModGetInfo($modid);

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'user', 'getall', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return $nullreturn;

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    } elseif (is_string($fieldlist)) {
        // support comma-separated field list
        $fieldlist = explode(',',$fieldlist);
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }

    $tree = xarModAPIFunc('dynamicdata','user', 'getancestors', array('moduleid' => $modid, 'itemtype' => $itemtype, 'base' => false));
    $objectarray = $itemsarray = array();
	foreach ($tree as $branch) {
		$object = & Dynamic_Object_Master::getObject(array('moduleid'  => $modid,
										   'itemtype'  => $branch['itemtype'],
										   'itemid'    => $itemid,
										   'fieldlist' => $fieldlist,
										   'join'      => $join,
										   'table'     => $table,
										   'status'    => $status));
		if (!isset($object) || empty($object->objectid)) return $nullreturn;
		if (!empty($itemid)) {
			$result = $object->getItem();
		}
		if (!empty($preview)) {
			$object->checkInput();
		}

		if (!empty($getobject)) {
			$objectarray[] = $object;
		} else {
			if (isset($result)) {
				if ($itemsarray == array()) {
					$itemsarray = $object->getFieldValues();}
				else {
					$itemsarray = array_merge($itemsarray, $object->getFieldValues());
				}
			}
		}
	}
    $objectData = $object->getFieldValues();
    return $objectData;
}

?>
