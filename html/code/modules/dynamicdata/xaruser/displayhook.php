<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return string output display string
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_user_displayhook(Array $args=array())
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

    $module_id = xarMod::getRegID($modname);
    if (empty($module_id)) {
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

    $object = & DataObjectMaster::getObject(array('moduleid' => $module_id,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid));
    if (!isset($object)) return;
    if (!$object->checkAccess('display'))
        return xarML('Display #(1) is forbidden', $object->label);

    $object->getItem();

    if (!empty($object->template)) {
        $template = $object->template;
    } else {
        $template = $object->name;
    }
    return xarTpl::module('dynamicdata','user','displayhook',
                        array('properties' => & $object->properties),
                        $template);
}

?>