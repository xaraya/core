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
use DataObjectFactory;
use DataPropertyMaster;
use xarController;
use xarMod;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


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
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security Check
        if (!$this->sec()->checkAccess('ViewDynamicData')) {
            return;
        }

        $data = [];

        if (!$this->var()->check('q', $q)) {
            return;
        }
        if (!$this->var()->check('dd_check', $dd_check)) {
            return;
        }
        if (!$this->var()->find('startnum', $startnum, 'int:0')) {
            return;
        }
        if (!$this->var()->find('numitems', $numitems, 'int:0')) {
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
            $data['q'] = isset($q) ? $this->var()->prep($q) : null;

            if (!$this->var()->check('module_id', $module_id, 'int')) {
                return;
            }
            if (!$this->var()->check('itemtype', $itemtype, 'int')) {
                return;
            }
            if (empty($module_id) && empty($itemtype)) {
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
            $object = $this->data()->getObjectInfo(
                ['moduleid' => $module_id,
                    'itemtype' => $itemtype]
            );
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
            $fields = $userapi->getprop(['module_id' => $module_id,
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
                $pagerurl = $this->mod()->getURL(
                    'user',
                    'search',
                    ['module_id' => ($module_id == $mymodid) ? null : $module_id,
                        'itemtype' => empty($itemtype) ? null : $itemtype,
                        'q' => $q,
                        'dd_check' => $dd_check]
                );
                // get the object
                // set context if available in function
                $object = $userapi->getobjectlist(['module_id' => $module_id,
                        'itemtype' => $itemtype,
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
                'link'     => $this->mod()->getURL(
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
}
