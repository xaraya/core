<?php
/**
 * Select dynamicdata for a new item
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * select dynamicdata for a new item - hook for ('item','new','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return string output display string
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_newhook(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('AddDynamicData')) return;

    extract($args);

    if (!isset($extrainfo)) throw new EmptyParameterException('extrainfo');

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    // don't allow hooking to yourself in DD
    if ($modname == 'dynamicdata') {
        return '';
    }

    $module_id = xarMod::getRegID($modname);
    if (empty($module_id)) {
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
        $itemid = 0;
    }
    $descriptorargs = DataObjectDescriptor::getObjectID(array('moduleid'  => $module_id,
                                       'itemtype'  => $itemtype));
    sys::import('modules.dynamicdata.class.objects.master');
    $object = DataObjectMaster::getObject(array('name' => $descriptorargs['name']));
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
    return xarTpl::module('dynamicdata','admin','newhook',
                        array('properties' => $properties),
                        $template);
}

?>
