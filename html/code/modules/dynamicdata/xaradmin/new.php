<?php
/**
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
 * Show add new item form
 *
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'id', $objectid,     1, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'id', $module_id,        182,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'id', $itemtype,     0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','str',   $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    $data = xarMod::apiFunc('dynamicdata','admin','menu');

    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'      => $name,
                                         'moduleid'  => $module_id,
                                         'itemtype'  => $itemtype,
                                         'join'      => $join,
                                         'table'     => $table,
                                         'itemid'    => $itemid,
                                         'tplmodule' => $tplmodule,
                                         'template'  => $template,
                                         ));
    if (!$myobject->checkAccess('create'))
        return xarResponse::Forbidden(xarML('Create #(1) is forbidden', $myobject->label));

    $args = $myobject->toArray();
    $data['object'] =& $myobject;
    $data['tplmodule'] = $args['tplmodule'];  //TODO: is this needed?

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // Makes this hooks call explictly from DD - why ???
    ////$modinfo = xarMod::getInfo($myobject->moduleid);
    //$modinfo = xarMod::getInfo(182);
    $myobject->callHooks('new');
    $data['hooks'] = $myobject->hookoutput;

    xarTplSetPageTitle(xarML('Manage - Create New Item in #(1)', $myobject->label));

    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new-' . $args['template'] . '.xt')) {
        return xarTplModule($args['tplmodule'],'admin','new',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','new',$data,$args['template']);
    }
}
?>