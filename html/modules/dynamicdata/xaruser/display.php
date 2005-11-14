<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 */
function dynamicdata_user_display($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    
/*  // we could also pass along the parameters to the template, and let it retrieve the object
    // but in this case, we'd need to retrieve the object label anyway
    return array('objectid' => $objectid,
                 'modid' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid);
*/

    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData')) return;
    }

    $myobject = & Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) return;
    $myobject->getItem();

    $data = array();
    $data['object'] =& $myobject;

    $modinfo = xarModGetInfo($myobject->moduleid);
    $item = array();
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $itemtype;
    $item['returnurl'] = xarModURL('dynamicdata','user','display',
                                   array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if(!isset($template)) {
        $template = $myobject->name;
    }
    // Return the template variables defined in this function
    return xarTplModule('dynamicdata','user','display',$data,$template);
}


?>
