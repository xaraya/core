<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\AdminApi;
use sys;

sys::import('modules.dynamicdata.method');


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

        $this->var()->check('objectid', $objectid);
        $this->var()->check('module_id', $module_id);
        $this->var()->check('itemtype', $itemtype);
        $this->var()->check('itemid', $itemid);
        $this->var()->check('preview', $preview);
        $this->var()->check('join', $join);
        $this->var()->check('table', $table);

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
