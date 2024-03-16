<?php
/**
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
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 * @return string|void output display string
 */
function dynamicdata_admin_form(array $args = [], $context = null)
{
    extract($args);

    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }

    if (empty($module_id)) {
        $module_id = xarMod::getRegID('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($itemid)) {
        $itemid = 0;
    }

    $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

    // set context if available in function
    $myobject = DataObjectFactory::getObject(['objectid' => $objectid,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid],
                                        $context);

    // Security
    if (!$myobject->checkAccess('create')) {
        return xarResponse::Forbidden(xarML('Create #(1) is forbidden', $myobject->label));
    }

    $data['object'] = & $myobject;

    $template = $myobject->name;
    return xarTpl::module('dynamicdata', 'admin', 'form', $data, $template);
}
