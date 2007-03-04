<?php
/**
 * Select dynamicdata for a new item
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
 * select dynamicdata for a new item - hook for ('item','new','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_newhook($args)
{
    extract($args);

    if (!isset($extrainfo)) throw new EmptyParameterException('extrainfo');

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }
    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', 'modifyhook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (isset($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } elseif (isset($objectid) && !empty($objectid)) {
        $itemid = $objectid;
    } else {
//        $itemid = 0;
        return "";
    }
    $object = & DataObjectMaster::getObject(array(
                                       'moduleid' => $modid,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid,
                                       'extend' => false));
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

    $properties = $object->getProperties();
    return xarTplModule('dynamicdata','admin','newhook',
                        array('properties' => $properties),
                        $template);
}

?>
