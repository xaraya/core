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
    if(!xarVarFetch('layout',   'str:1' , $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}

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

    $object = DataObjectMaster::getObjectInfo(
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype,
                                  'join'     => $join,
                                  'table'    => $table));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        $objectid = 0;
        $label = xarML('Dynamic Data Objects');
        $param = '';
    }
    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    $data = xarModAPIFunc('dynamicdata','user','menu');
    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['numitems'] = $numitems;
    $data['label'] = $label;
    $data['join'] = $join;
    $data['table'] = $table;
    $data['catid'] = $catid;
    $data['layout'] = $layout;

/*  // we could also retrieve the object list here, and pass that along to the template
    $numitems = 30;
    $mylist = & DataObjectMaster::getObjectList(array('objectid' => $objectid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'status'   => 1));
    $mylist->getItems(array('numitems' => $numitems,
                            'startnum' => $startnum));

    $data['object'] = & $mylist;
*/
    return $data;
}

?>
