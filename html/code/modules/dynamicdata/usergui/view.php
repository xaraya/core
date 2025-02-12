<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\UserGui;
use Xaraya\DataObject\AdminApi;
use DataObjectFactory;
use xarController;
use xarMod;
use xarModVars;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata user view function
 * @extends MethodClass<UserGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * view a list of items
     * This is a standard function to provide an overview of all of the items
     * available from the module.
     * @param array<string,mixed> $args
     * @return string|void output display string
     * @see UserGui::view()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Old-style arguments
        if (!$this->var()->check('objectid', $objectid, 'int')) {
            return;
        }
        if (!$this->var()->check('module_id', $module_id, 'int')) {
            return;
        }
        if (!$this->var()->check('moduleid', $moduleid, 'int')) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype, 'int')) {
            return;
        }
        // New-style arguments
        if (!$this->var()->check('itemid', $itemid, 'int')) {
            return;
        }
        if (!$this->var()->check('name', $name)) {
            return;
        }

        if (!$this->var()->check('startnum', $startnum, 'int')) {
            return;
        }
        if (!$this->var()->check('numitems', $numitems, 'int')) {
            return;
        }
        if (!$this->var()->check('sort', $sort)) {
            return;
        }
        if (!$this->var()->check('catid', $catid)) {
            return;
        }
        if (!$this->var()->find('layout', $layout, 'str:1', 'default')) {
            return;
        }
        if (!$this->var()->find('tplmodule', $tplmodule, 'isset', 'dynamicdata')) {
            return;
        }
        if (!$this->var()->check('template', $template)) {
            return;
        }

        // Override if needed from argument array
        extract($args);

        // Support old-style arguments
        if (empty($itemid) && !empty($objectid)) {
            $itemid = $objectid;
        }
        if (empty($module_id) && !empty($moduleid)) {
            $module_id = $moduleid;
        }
        if (empty($module_id)) {
            $module_id = $this->mod()->getRegID('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }

        // Default number of items per page in user view
        if (empty($numitems)) {
            $numitems = $this->mod()->getVar('items_per_page');
        }

        // Note: we need to pass all relevant arguments ourselves here
        // set context if available in function
        $object = $this->data()->getObjectList(
            ['objectid'  => $itemid,
                'name'      => $name,
                'startnum'  => $startnum,
                'numitems'  => $numitems,
                'sort'      => $sort,
                'catid'     => $catid,
                'layout'    => $layout,
                'tplmodule' => $tplmodule,
                'template'  => $template,
            ]
        );

        if (!$object->checkAccess('view')) {
            $msg = $this->ml('View #(1) is forbidden', $object->label);
            return $this->ctl()->forbidden($msg);
        }

        // Pass back the relevant variables to the template if necessary
        $data = $object->toArray();

        // Count the number of items matching the preset arguments - do this before getItems()
        $object->countItems();

        // Get the selected items using the preset arguments
        $object->getItems();

        // Pass the object list to the template
        $data['object'] = $object;

        // TODO: is this needed?
        $data = array_merge($data, $adminapi->menu());
        // TODO: remove this when we turn all the moduleid into module_id
        $data['module_id'] = $data['moduleid'];
        // TODO: another stray
        $data['catid'] = $catid;
        $data['context'] ??= $object->getContext();

        $this->tpl()->setPageTitle($this->ml('View #(1)', $object->label));

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view-' . $data['template'] . '.xt')) {
            return $this->tpl()->module($data['tplmodule'], 'user', 'view', $data, $data['template']);
        } else {
            return $this->tpl()->module('dynamicdata', 'user', 'view', $data, $args['template']);
        }
    }
}
