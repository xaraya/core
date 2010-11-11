<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * list some items in a template
 *
 * @param $args array containing the items or fields to show
 * @return string output display string
 */

function dynamicdata_userapi_showview(Array $args=array())
{
    extract($args);

    $args['fallbackmodule'] = 'current';
    $descriptor = new DataObjectDescriptor($args);
    $args = $descriptor->getArgs();

    // do we want to count?
    if(empty($count)) $args['count'] = false;

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        $args['count'] = count($items);
        return xarTplModule('dynamicdata','user','showview',
                            $args,
                            $template);
    }

// Note: fetching input variables doesn't normally belong in APIs, but this is
//       used by the xar:data-view tag when no object or items are specified !

    if (!isset($itemids)) {
        if (!xarVarFetch('itemids', 'isset', $itemids,  NULL, XARVAR_DONT_SET)) {return;}
    }

    if (!isset($sort)) {
        if (!xarVarFetch('sort', 'isset', $sort,  NULL, XARVAR_DONT_SET)) {return;}
    }

    if (!isset($numitems)) {
        if (!xarVarFetch('numitems', 'isset', $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    }

    if (!isset($startnum)) {
        if (!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    }

    if (isset($table)) {
        $table = xarDB::getPrefix() . '_' . $table;
    } else {
        $table = '';
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
        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    }

    // select in some category
    if (empty($catid)) $catid = '';

    $object = DataObjectMaster::getObjectList(array('moduleid'  => $args ['moduleid'],
                                           'itemtype'  => $args ['itemtype'],
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'table'      => $table,
                                           'where' => $where,
                                           'fieldlist' => $myfieldlist,
                                           'catid' => $catid,
                                           'groupby' => $groupby,
                                           'status' => $status));
    if (!isset($object)) return;
    if (!$object->checkAccess('view'))
        return xarML('View #(1) is forbidden', $object->label);
    // Count before numitems!
    $numthings = 0;
    if($args['count']) {
        $numthings = $object->countItems();
    }

    // Get the selected items
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

    // TODO: stopgap: remove once we let the descriptor do this
    if (empty($layout)) {
        $layout = 'default';
    }
    if (empty($tplmodule)) {
        $tplmodule = 'dynamicdata';
    }
    if (empty($template)) {
        $template = '';
    }
    return $object->showView(array('layout'    => $layout,
                                   'tplmodule' => $tplmodule,
                                   'template'  => $template,
                                   'linklabel' => $linklabel,
                                   'linkfunc'  => $linkfunc,
                                   'param'     => $param,
                                   'pagerurl'  => $pagerurl,
                                   'linkfield' => $linkfield,
                                   'itemcount' => $numthings));
}

?>
