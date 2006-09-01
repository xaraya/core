<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_user_displayhook($args)
{
    extract($args);

    if (!isset($extrainfo)) throw new EmptyParameterException('extrainfo');

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('object ID', 'user', 'displayhook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (is_array($extrainfo) && !empty($extrainfo['module']) && is_string($extrainfo['module'])) {
        $modname = $extrainfo['module'];
    } else {
        $modname = xarModGetName();
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name ' . $modname, 'user', 'displayhook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (is_array($extrainfo) && isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
// TODO: find some better way to do this !
    } elseif (xarVarIsCached('Hooks.display','itemtype')) {
        $itemtype = xarVarGetCached('Hooks.display','itemtype');
    } else {
        $itemtype = null;
    }

    if (is_array($extrainfo) && isset($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

	$object = & Dynamic_Object_Master::getObject(array('moduleid' => $modid,
									   'itemtype' => $itemtype,
									   'itemid'   => $itemid,
									   'extend' => false));
	if (!isset($object)) return;

	$object->getItem();

	if (!empty($object->template)) {
		$template = $object->template;
	} else {
		$template = $object->name;
	}
	return xarTplModule('dynamicdata','user','displayhook',
						array('properties' => & $object->properties),
						$template);
}

?>
