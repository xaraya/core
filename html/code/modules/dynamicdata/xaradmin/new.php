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
 * Show add new item form
 *
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 * @return string|void output display string
 */
function dynamicdata_admin_new(array $args = [], $context = null)
{
    extract($args);

    if(!xarVar::fetch('objectid', 'id', $objectid, 1, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'id', $module_id, 182, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'id', $itemtype, 0, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'isset', $itemid, 0, xarVar::DONT_SET)) {
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
    if(!xarVar::fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('notfresh', 'isset', $notfresh, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tplmodule', 'str', $tplmodule, null, xarVar::DONT_SET)) {
        return;
    }

    $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

    // set context if available in function
    $myobject = DataObjectFactory::getObject(
        ['objectid' => $objectid,
        'name'      => $name,
        'moduleid'  => $module_id,
        'itemtype'  => $itemtype,
        'join'      => $join,
        'table'     => $table,
        'itemid'    => $itemid,
        'tplmodule' => $tplmodule,
        'template'  => $template],
        $context
    );
    // Security
    if (empty($myobject)) {
        return xarResponse::NotFound();
    }
    if (!$myobject->checkAccess('create')) {
        return xarResponse::Forbidden(xarML('Create #(1) is forbidden', $myobject->label));
    }

    $args = $myobject->toArray();
    $data['object'] = & $myobject;
    $data['tplmodule'] = $args['tplmodule'];  //TODO: is this needed?

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSec::genAuthKey();

    // Makes this hooks call explictly from DD - why ???
    ////$modinfo = xarMod::getInfo($myobject->moduleid);
    //$modinfo = xarMod::getInfo(182);
    $myobject->callHooks('new');
    $data['hooks'] = $myobject->hookoutput;

    xarTpl::setPageTitle(xarML('Manage - Create New Item in #(1)', $myobject->label));

    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new-' . $args['template'] . '.xt')) {
        return xarTpl::module($args['tplmodule'], 'admin', 'new', $data, $args['template']);
    } else {
        return xarTpl::module('dynamicdata', 'admin', 'new', $data, $args['template']);
    }
}
