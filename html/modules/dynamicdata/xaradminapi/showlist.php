<?php
/**
 * List items in a template
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * list some items in a template
 *
 * @param $args array containing the items or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showlist($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        return xarTplModule('dynamicdata','admin','showlist',
                            array('items' => $items,
                                  'labels' => $labels,
                                  'layout' => $layout),
                            $template);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($module)) {
        $modname = xarModGetName();
    } else {
        $modname = $module;
    }

    if (is_numeric($modname)) {
        $modid = $modname;
        $modinfo = xarModGetInfo($modid);
        $modname = $modinfo['name'];
    } else {
        $modid = xarModGetIDFromName($modname);
    }
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', 'showlist', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

// TODO: what kind of security checks do we want/need here ?
    // don't bother if you can't edit anything anyway
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        if (!xarVarFetch('itemids', 'isset', $itemids,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the sort via input variables if necessary
    if (!isset($sort)) {
        if (!xarVarFetch('sort', 'isset', $sort,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the numitems via input variables if necessary
    if (!isset($numitems)) {
        if (!xarVarFetch('numitems', 'isset', $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the startnum via input variables if necessary
    if (!isset($startnum)) {
        if (!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // don't try getting the where clause via input variables, obviously !
    if (empty($where)) {
        $where = '';
    }
    if (empty($groupby)) {
        $groupby = '';
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
        $status = null;
    } else {
        $myfieldlist = null;
        // get active properties only (+ not the display only ones)
        $status = 1;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }
    // select in some category
    if (empty($catid)) {
        $catid = '';
    }

    // check the URL parameter for the item id used by the module (e.g. exid, aid, ...)
    if (empty($param)) {
        $param = '';
    }

    $object = & Dynamic_Object_Master::getObjectList(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $myfieldlist,
                                           'join' => $join,
                                           'table' => $table,
                                           'catid' => $catid,
                                           'groupby' => $groupby,
                                           'status' => $status));
    $object->getItems();

    return $object->showList(array('layout'   => $layout,
                                   'template' => $template));
}
?>
