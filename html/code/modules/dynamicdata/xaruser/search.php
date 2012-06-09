<?php
/**
 * Search dynamic data
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.htm
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * search dynamicdata (called as hook from search module, or directly with pager)
 *
 * @param string q the query. The query is used in an SQL LIKE query
 * @param int startnum
 * @param array dd_check
 * @param int numitems The number of items to get
 * @return array output of the items found
 */
function dynamicdata_user_search(Array $args=array())
{
// Security Check
    if(!xarSecurityCheck('ViewDynamicData')) return;

    $data = array();

    if (!xarVarFetch('q', 'isset', $q, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('dd_check', 'isset', $dd_check, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('startnum', 'int:0', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('numitems', 'int:0', $numitems,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (empty($dd_check)) {
        $dd_check = array();
    }

    // see if we're coming from the search hook or not
    if (isset($args['objectid'])) {
        $data['ishooked'] = 1;
    } else {
        $data['ishooked'] = 0;
        $data['q'] = isset($q) ? xarVarPrepForDisplay($q) : null;

        if(!xarVarFetch('module_id',    'int',   $module_id,     NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
        if (empty($module_id) && empty($itemtype)) {
            $data['gotobject'] = 0;
        } else {
            $data['gotobject'] = 1;
        }
        if (empty($module_id)) {
            $module_id = xarMod::getRegID('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
    }
    // TODO: move this to the varFetch?
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = 20;
    }

    $label = xarML('Dynamic Data');
    if (empty($data['ishooked']) && !empty($data['gotobject'])) {
        // get the selected object
        $objects = array();
        $object = DataObjectMaster::getObjectInfo(
                                array('moduleid' => $module_id,
                                      'itemtype' => $itemtype));
        if (!empty($object)) {
            $objects[$object['objectid']] = $object;
            $label = $object['label'];
        }
    } else {
        // get items from the objects table
        $objects = DataObjectMaster::getObjects();
    }

    if (empty($data['ishooked'])) {
        xarTpl::setPageTitle(xarML('Search #(1)', $label));
    }

    $data['items'] = array();
    $mymodid = xarMod::getRegID('dynamicdata');
    if ($data['ishooked']) {
        $myfunc = 'view';
    } else {
        $myfunc = 'search';
    }
    if (!empty($q)) {
        $quoted = str_replace("'","\\'",$q);
        $quoted = str_replace("%","\\%",$quoted);
        $quoted = str_replace("_","\\_",$quoted);
    }
    foreach ($objects as $itemid => $object) {
        // skip the internal objects
        if ($itemid < 3) continue;
        $module_id = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($module_id != $mymodid) {
            continue;
        }
        $label = $object['label'];
        $itemtype = $object['itemtype'];
        $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                                array('module_id' => $module_id,
                                      'itemtype' => $itemtype));
        $wherelist = array();
        foreach ($fields as $name => $field) {
            if (!empty($dd_check[$field['id']])) {
                $fields[$name]['checked'] = 1;
                if (!empty($q)) {
                    $wherelist[$name] = " LIKE '%" . $quoted . "%'";
                }
            }
        }
        if (!empty($q) && count($wherelist) > 0) {
            //$where = join(' or ',$wherelist);
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
            $pagerurl = xarModURL('dynamicdata','user','search',
                                  array('module_id' => ($module_id == $mymodid) ? null : $module_id,
                                        'itemtype' => empty($itemtype) ? null : $itemtype,
                                        'q' => $q,
                                        'dd_check' => $dd_check));
            // get the object
            $object = xarMod::apiFunc('dynamicdata','user','getobjectlist',
                                    array('module_id' => $module_id,
                                          'itemtype' => $itemtype,
                                          //'where' => $where,
                                          'startnum' => $startnum,
                                          'numitems' => $numitems,
                                          //'pagerurl' => $pagerurl,
                                          'layout' => 'list',
                                          'status' => $status));
            if (!$object->checkAccess('view'))
                continue;
            // add the where clauses directly here to avoid quoting issues
            $join = '';
            foreach ($wherelist as $name => $clause) {
                $object->addWhere($name, $clause, $join);
                $join = 'or';
            }
            // count the items
            $object->countItems();
            // get the items
            $object->getItems();
            // show the items
            $result = $object->showView();
        } else {
            $result = null;
        }
        // nice(r) URLs
        if ($module_id == $mymodid) {
            $module_id = null;
        }
        if ($itemtype == 0) {
            $itemtype = null;
        }
        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user',$myfunc,
                                                         array('module_id' => $module_id,
                                                               'itemtype' => $itemtype)),
                                 'label'    => $label,
                                 'module_id'    => $module_id,
                                 'itemtype' => $itemtype,
                                 'fields'   => $fields,
                                 'result'   => $result,
                                );
    }

    return $data;
}

?>
