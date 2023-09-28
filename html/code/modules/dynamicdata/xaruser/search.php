<?php
/**
 * Search dynamic data
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * search dynamicdata (called as hook from search module, or directly with pager)
 *
 * @param array<string, mixed> $args
 * with
 *     string $args['q'] the query. The query is used in an SQL LIKE query
 *        int $args['startnum']
 *      array $args['dd_check']
 *        int $args['numitems'] The number of items to get
 * @return array<mixed>|void output of the items found
 */
function dynamicdata_user_search(array $args = [])
{
    // Security Check
    if(!xarSecurity::check('ViewDynamicData')) {
        return;
    }

    $data = [];

    if (!xarVar::fetch('q', 'isset', $q, null, xarVar::DONT_SET)) {
        return;
    }
    if (!xarVar::fetch('dd_check', 'isset', $dd_check, null, xarVar::DONT_SET)) {
        return;
    }
    if (!xarVar::fetch('startnum', 'int:0', $startnum, null, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('numitems', 'int:0', $numitems, null, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (empty($dd_check)) {
        $dd_check = [];
    }

    // see if we're coming from the search hook or not
    if (isset($args['objectid'])) {
        $data['ishooked'] = 1;
    } else {
        $data['ishooked'] = 0;
        $data['q'] = isset($q) ? xarVar::prepForDisplay($q) : null;

        if(!xarVar::fetch('module_id', 'int', $module_id, null, xarVar::DONT_SET)) {
            return;
        }
        if(!xarVar::fetch('itemtype', 'int', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
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
        $objects = [];
        $object = DataObjectMaster::getObjectInfo(
            ['moduleid' => $module_id,
                                      'itemtype' => $itemtype]
        );
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

    $data['items'] = [];
    $mymodid = xarMod::getRegID('dynamicdata');
    if ($data['ishooked']) {
        $myfunc = 'view';
    } else {
        $myfunc = 'search';
    }
    if (!empty($q)) {
        $quoted = str_replace("'", "\\'", $q);
        $quoted = str_replace("%", "\\%", $quoted);
        $quoted = str_replace("_", "\\_", $quoted);
    }
    foreach ($objects as $itemid => $object) {
        // skip the internal objects
        if ($itemid < 3) {
            continue;
        }
        $module_id = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($module_id != $mymodid) {
            continue;
        }
        $label = $object['label'];
        $itemtype = $object['itemtype'];
        $fields = xarMod::apiFunc(
            'dynamicdata',
            'user',
            'getprop',
            ['module_id' => $module_id,
                                      'itemtype' => $itemtype]
        );
        $wherelist = [];
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
            $pagerurl = xarController::URL(
                'dynamicdata',
                'user',
                'search',
                ['module_id' => ($module_id == $mymodid) ? null : $module_id,
                                        'itemtype' => empty($itemtype) ? null : $itemtype,
                                        'q' => $q,
                                        'dd_check' => $dd_check]
            );
            // get the object
            $object = xarMod::apiFunc(
                'dynamicdata',
                'user',
                'getobjectlist',
                ['module_id' => $module_id,
                                          'itemtype' => $itemtype,
                                          //'where' => $where,
                                          'startnum' => $startnum,
                                          'numitems' => $numitems,
                                          //'pagerurl' => $pagerurl,
                                          'layout' => 'list',
                                          'status' => $status]
            );
            if (!$object->checkAccess('view')) {
                continue;
            }
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
        $data['items'][] = [
                                 'link'     => xarController::URL(
                                     'dynamicdata',
                                     'user',
                                     $myfunc,
                                     ['module_id' => $module_id,
                                                               'itemtype' => $itemtype]
                                 ),
                                 'label'    => $label,
                                 'module_id'    => $module_id,
                                 'itemtype' => $itemtype,
                                 'fields'   => $fields,
                                 'result'   => $result,
                                ];
    }

    return $data;
}
