<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserGui;
use Xaraya\Modules\DynamicData\UserApi;
use DataPropertyMaster;

/**
 * dynamicdata user search function
 * @extends MethodClass<UserGui>
 */
class SearchMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * search dynamicdata (called as hook from search module, or directly with pager)
     * @param array<string,mixed> $args
     * with
     *     string $args['q'] the query. The query is used in an SQL LIKE query
     *        int $args['startnum']
     *      array $args['dd_check']
     *        int $args['numitems'] The number of items to get
     * @return array|void output of the items found
     * @see UserGui::search()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security Check
        if (!$this->sec()->checkAccess('ViewDynamicData')) {
            return;
        }

        $data = [];

        $this->var()->check('q', $q);
        $this->var()->check('dd_check', $dd_check);
        $this->var()->check('startnum', $startnum, 'int:0');
        $this->var()->check('numitems', $numitems, 'int:0');
        if (empty($dd_check)) {
            $dd_check = [];
        }

        // see if we're coming from the search hook or not
        if (isset($args['objectid'])) {
            $data['ishooked'] = 1;
        } else {
            $data['ishooked'] = 0;
            $data['q'] = isset($q) ? $this->prep()->text($q) : null;

            $this->var()->check('name', $name, 'str:1');
            $this->var()->check('module_id', $module_id, 'int');
            $this->var()->check('itemtype', $itemtype, 'int');
            if (empty($name) && empty($module_id) && empty($itemtype)) {
                $data['gotobject'] = 0;
            } else {
                $data['gotobject'] = 1;
            }
            if (empty($module_id)) {
                $module_id = $this->mod()->getRegID('dynamicdata');
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

        $label = $this->ml('Dynamic Data');
        if (empty($data['ishooked']) && !empty($data['gotobject'])) {
            // get the selected object
            $objects = [];
            if (empty($name)) {
                $info = $this->data()->getObjectID([
                    'moduleid' => $module_id,
                    'itemtype' => $itemtype,
                ]);
                $name = $info['name'];
                if (empty($name)) {
                    $msg = $this->ml('Unknown dynamic data object');
                    return $this->ctl()->notFound($msg);
                }
            }
            $object = $this->data()->getObjectInfo(['name' => $name]);
            if (!empty($object)) {
                $objects[$object['objectid']] = $object;
                $label = $object['label'];
            }
        } else {
            // get items from the objects table
            $objects = $this->data()->getObjects();
        }

        if (empty($data['ishooked'])) {
            $this->tpl()->setPageTitle($this->ml('Search #(1)', $label));
        }

        $data['startnum'] = $startnum;
        $data['numitems'] = $numitems;
        $data['items'] = [];
        $mymodid = $this->mod()->getRegID('dynamicdata');
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
            if ($itemid < 4) {
                continue;
            }
            $module_id = $object['moduleid'];
            // don't show data "belonging" to other modules for now
            if ($module_id != $mymodid) {
                continue;
            }
            $name = $object['name'];
            $label = $object['label'];
            $itemtype = $object['itemtype'];
            $fields = $userapi->getprop(['name' => $name]);
            $wherelist = [];
            foreach ($fields as $prop => $field) {
                if (!empty($dd_check[$field['id']])) {
                    $fields[$prop]['checked'] = 1;
                    if (!empty($q)) {
                        $wherelist[$prop] = " LIKE '%" . $quoted . "%'";
                    }
                }
            }
            if (!empty($q) && count($wherelist) > 0) {
                //$where = join(' or ',$wherelist);
                $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
                $pagerurl = $this->mod()->getURL(
                    'user',
                    'search',
                    ['name' => $name,
                        'q' => $q,
                        'dd_check' => $dd_check]
                );
                // get the object
                // set context if available in function
                $object = $userapi->getobjectlist([
                    'name' => $name,
                    //'where' => $where,
                    'startnum' => $startnum,
                    'numitems' => $numitems,
                    //'pagerurl' => $pagerurl,
                    'layout' => 'list',
                    'status' => $status]);
                if (!$object->checkAccess('view')) {
                    continue;
                }
                // add the where clauses directly here to avoid quoting issues
                $join = '';
                foreach ($wherelist as $prop => $clause) {
                    $object->addWhere($prop, $clause, $join);
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
                'link'     => $this->mod()->getURL(
                    'user',
                    $myfunc,
                    ['name' => $name]
                ),
                'name'    => $name,
                'label'    => $label,
                'module_id'    => $module_id,
                'itemtype' => $itemtype,
                'fields'   => $fields,
                'result'   => $result,
            ];
        }

        return $data;
    }
}
