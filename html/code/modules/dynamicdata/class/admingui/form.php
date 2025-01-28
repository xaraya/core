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

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\AdminApi;
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
     * @see AdminGui::form()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!$this->var()->check('objectid', $objectid)) {
            return;
        }
        if (!$this->var()->check('module_id', $module_id)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('preview', $preview)) {
            return;
        }
        if (!$this->var()->check('join', $join)) {
            return;
        }
        if (!$this->var()->check('table', $table)) {
            return;
        }

        if (empty($module_id)) {
            $module_id = $this->mod()->getRegID('dynamicdata');
        }
        if (!isset($itemtype)) {
            $itemtype = 0;
        }
        if (!isset($itemid)) {
            $itemid = 0;
        }

        $data = $adminapi->menu();

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid]
        );

        // Security
        if (!$myobject->checkAccess('create')) {
            $msg = $this->ml('Create #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $data['object'] = & $myobject;
        $data['context'] ??= $myobject->getContext();

        $template = $myobject->name;
        return $this->tpl()->module('dynamicdata', 'admin', 'form', $data, $template);
    }
}
