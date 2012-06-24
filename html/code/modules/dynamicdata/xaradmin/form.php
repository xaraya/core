<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 * @return string output display string
 */
function dynamicdata_admin_form(Array $args=array())
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $module_id,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}

    if (empty($module_id)) {
        $module_id = xarMod::getRegID('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($itemid)) {
        $itemid = 0;
    }

    $data = xarMod::apiFunc('dynamicdata','admin','menu');

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    
    // Security
    if (!$myobject->checkAccess('create'))
        return xarResponse::Forbidden(xarML('Create #(1) is forbidden', $myobject->label));

    $data['object'] =& $myobject;

    $template = $myobject->name;
    return xarTpl::module('dynamicdata','admin','form',$data,$template);
}

?>
