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
    if(!xarVarFetch('modid',    'int',   $modid,     182,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','str',   $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

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

    if($modid == 182) {
        // Dynamicdata module is special
        $ancestor = array('objectid' => $objectid, 'modid' => $modid, 'itemtype' => $itemtype);
    } else {
        if (isset($objectid)) {
            $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('objectid' => $objectid));
        } else {
            $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('moduleid' => $modid,'itemtype' => $itemtype));
        }
    }
    $itemtype = $ancestor['itemtype'];

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) return;
    $args = $myobject->toArray();
    $myobject->getItem();

    $data = array();
    //$data['object'] =& $myobject;

    $modinfo = xarModGetInfo($myobject->moduleid);
    $item = array();
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $itemtype;
    $item['returnurl'] = xarModURL($tplmodule,'user','display',
                                   array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    // First transform hooks, create an array of things eligible and pass that along
    $totransform = array(); $totransform['transform'] = array(); // we must do this, otherwise we lose track of what got transformed
    foreach($myobject->properties as $pname => $pobj) {
        // *never* transform an ID
        // TODO: there is probably lots more to skip here.
        if($pobj->type == '21') continue;
        $totransform['transform'][] = $pname;
        $totransform[$pname] = $pobj->value;
    }
    $transformed = xarModCallHooks('item','transform',$myobject->itemid, $totransform, $modinfo['name'],$myobject->itemtype);
    // Ok, we got the transformed values, now what?
    foreach($transformed as $pname => $tvalue) {
        if($pname == 'transform') continue;
        $myobject->properties[$pname]->value = $tvalue;
    }

    // *Now* we can set the data stuff
    $data['object'] =& $myobject;

    // Display hooks
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    // Return the template variables defined in this function
    if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/user-display.xd') ||
        file_exists('modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xd')) {
        return xarTplModule($args['tplmodule'],'user','display',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','user','display',$data,$args['template']);
    }
}


?>
