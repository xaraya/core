<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserApi;
use DataObjectDescriptor;
use DataObjectFactory;
use DataPropertyMaster;
use xarDB;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata userapi showview function
 * @extends MethodClass<UserApi>
 */
class ShowviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * // TODO: move this to some common place in Xaraya (base module ?)
     * list some items in a template
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return string|void output display string
     * @see UserApi::showview()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $args['fallbackmodule'] = 'current';
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // do we want to count?
        if (empty($count)) {
            $args['count'] = false;
        }
        if (empty($template)) {
            $template = '';
        }

        // we got everything via template parameters
        if (isset($items) && is_array($items)) {
            $args['count'] = count($items);
            return $this->tpl()->module(
                'dynamicdata',
                'user',
                'showview',
                $args,
                $template
            );
        }

        // Note: fetching input variables doesn't normally belong in APIs, but this is
        //       used by the xar:data-view tag when no object or items are specified !

        if (!isset($itemids)) {
            if (!$this->var()->check('itemids', $itemids)) {
                return;
            }
        }

        if (!isset($sort)) {
            if (!$this->var()->check('sort', $sort)) {
                return;
            }
        }

        if (!isset($numitems)) {
            if (!$this->var()->check('numitems', $numitems)) {
                return;
            }
        }

        if (!isset($startnum)) {
            if (!$this->var()->check('startnum', $startnum)) {
                return;
            }
        }

        if (isset($table)) {
            $table = $this->db()->getPrefix() . '_' . $table;
        } else {
            $table = '';
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
                $myfieldlist = explode(',', $fieldlist);
                // and array of fields
            } elseif (is_array($fieldlist)) {
                $myfieldlist = $fieldlist;
            } else {
                $myfieldlist = null;
            }
            $status = null;
        } else {
            $myfieldlist = null;
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        }

        // select in some category
        if (empty($catid)) {
            $catid = '';
        }

        // set context if available in function
        $object = $this->data()->getObjectList(
            ['objectid'  => $args ['objectid'],
                'itemids' => $itemids,
                'sort' => $sort,
                'numitems' => $numitems,
                'startnum' => $startnum,
                'table'      => $table,
                'where' => $where,
                'fieldlist' => $myfieldlist,
                'catid' => $catid,
                'groupby' => $groupby,
                'status' => $status]
        );
        if (!isset($object) || empty($object->label)) {
            return;
        }
        if (!$object->checkAccess('view')) {
            return $this->ml('View #(1) is forbidden', $object->label);
        }

        // We need to get the total count before adding numitems!
        if ($args['count']) {
            $itemcount = $object->countItems();
        }

        // Get the selected items
        $object->getItems();

        // label to use for the display link (if you don't use linkfield)
        if (empty($linklabel)) {
            $linklabel = '';
        }

        // function to use in the display link
        if (empty($linkfunc)) {
            $linkfunc = '';
        }

        // URL parameter for the item id in the display link (e.g. exid, aid, uid, ...)
        if (empty($param)) {
            $param = '';
        }

        // field to add the display link to (otherwise it'll be in a separate column)
        if (empty($linkfield)) {
            $linkfield = '';
        }

        // current URL for the pager (defaults to current URL)
        if (empty($pagerurl)) {
            $pagerurl = '';
        }

        // TODO: stopgap: remove once we let the descriptor do this
        if (empty($layout)) {
            $layout = 'default';
        }
        if (empty($tplmodule)) {
            $tplmodule = 'dynamicdata';
        }
        return $object->showView([
            'layout'    => $layout,
            'tplmodule' => $tplmodule,
            'template'  => $template,
            'linklabel' => $linklabel,
            'linkfunc'  => $linkfunc,
            'param'     => $param,
            'pagerurl'  => $pagerurl,
            'linkfield' => $linkfield,
        ]);
    }
}
