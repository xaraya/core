<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataObjectFactory;
use xarController;
use xarMod;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin form function
 * @extends MethodClass<AdminGui>
 */
class FormMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * add new item
     * This is a standard function that is called whenever an administrator
     * wishes to create a new module item
     * @return string|void output display string
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
            return;
        }

        if (empty($module_id)) {
            $module_id = xarMod::getRegID('dynamicdata');
        }
        if (!isset($itemtype)) {
            $itemtype = 0;
        }
        if (!isset($itemid)) {
            $itemid = 0;
        }

        $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

        // set context if available in function
        $myobject = DataObjectFactory::getObject(
            ['objectid' => $objectid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid],
            $this->getContext()
        );

        // Security
        if (!$myobject->checkAccess('create')) {
            $msg = $this->ml('Create #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $data['object'] = & $myobject;
        $data['context'] ??= $myobject->getContext();

        $template = $myobject->name;
        return xarTpl::module('dynamicdata', 'admin', 'form', $data, $template);
    }
}
