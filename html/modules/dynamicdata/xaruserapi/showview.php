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
// TODO: move this to some common place in Xaraya (base module ?)
 * list some items in a template
 *
 * @param $args array containing the items or fields to show
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showview($args)
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
    if (empty($tplmodule)) {
        $tplmodule = 'dynamicdata';
    }

    // do we want to count?
    if(empty($count)) $count=false;

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        return xarTplModule('dynamicdata','user','showview',
                            array('items' => $items,
                                  'labels' => $labels,
                                  'layout' => $layout,
                                  'count'  => count($items)), // no overhead, count anyway
                            $template);
    }

    if (empty($modid)) {
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
    } else {
            $modinfo = xarModGetInfo($modid);
            $modname = $modinfo['name'];
    }
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'user', 'showview', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

    // TODO: what kind of security checks do we want/need here ?
    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

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
    if (empty($where)) $where = '';
    if (empty($groupby)) $groupby = '';

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
        $status = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
    }

    // join a module table to a dynamic object
    if (empty($join)) $join = '';

    // make some database table available via DD
    if (empty($table)) $table = '';

    // select in some category
    if (empty($catid)) $catid = '';

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
                                           'status' => $status,
                                           'extend' => !empty($extend)));
    if (!isset($object)) return;
    // Count before numitems!
    $numthings = 0;
    if($count) {
        $numthings = $object->countItems();
    }
    $object->getItems();

    // label to use for the display link (if you don't use linkfield)
    if (empty($linklabel)) $linklabel = '';

    // function to use in the display link
    if (empty($linkfunc)) $linkfunc = '';

    // URL parameter for the item id in the display link (e.g. exid, aid, uid, ...)
    if (empty($param)) $param = '';

    // field to add the display link to (otherwise it'll be in a separate column)
    if (empty($linkfield)) $linkfield = '';

    // current URL for the pager (defaults to current URL)
    if (empty($pagerurl)) $pagerurl = '';

    return $object->showView(array('layout'    => $layout,
                                   'template'  => $template,
                                   'linklabel' => $linklabel,
                                   'linkfunc'  => $linkfunc,
                                   'param'     => $param,
                                   'pagerurl'  => $pagerurl,
                                   'linkfield' => $linkfield,
                                   'count'     => $numthings,
                                   'tplmodule' => $tplmodule));
}

?>
