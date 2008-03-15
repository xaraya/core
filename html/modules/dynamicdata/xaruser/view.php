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
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 *
 * @return array
 */
function dynamicdata_user_view($args)
{
    if(!xarVarFetch('objectid', 'int',   $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'int',   $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    // Override if needed from argument array
    extract($args);

    // Security measure for table browsing
    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData')) return;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $object = DataObjectMaster::getObjectList(
                            array('objectid'  => $objectid,
                                  'moduleid'  => $modid,
                                  'itemtype'  => $itemtype,
                                  'join'      => $join,
                                  'table'     => $table,
                                  'tplmodule' => $tplmodule,
                                  'template'  => $template,
                                  ));
    $data = $object->toArray();
    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    // TODO: is this needed?
    $data = array_merge($data,xarModAPIFunc('dynamicdata','admin','menu'));
    // TODO: remove this when we turn all the moduleid into modid
    $data['modid'] = $data['moduleid'];
    // TODO: another stray
    $data['catid'] = $catid;

    if (file_exists('modules/' . $data['tplmodule'] . '/xartemplates/user-view.xd') ||
        file_exists('modules/' . $data['tplmodule'] . '/xartemplates/user-view-' . $data['template'] . '.xd')) {
        return xarTplModule($data['tplmodule'],'user','view',$data,$data['template']);
    } else {
        return xarTplModule('dynamicdata','user','view',$data,$args['template']);
    }
}

?>
