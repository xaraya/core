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
use DataObjectFactory;
use Exception;
use xarController;
use xarMod;
use xarModVars;
use xarSecurity;
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View items
     * @return string|void output display string
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        $this->var()->check('itemid', $itemid, 'int', 1);
        $this->var()->check('name', $name);
        $this->var()->check('startnum', $startnum, 'int');
        $this->var()->check('numitems', $numitems, 'int');
        $this->var()->check('sort', $sort);
        $this->var()->check('catid', $catid);
        $this->var()->check('layout', $layout, 'str:1', 'default');
        $this->var()->check('tplmodule', $tplmodule, 'isset', 'dynamicdata');
        $this->var()->check('template', $template);

        // Override if needed from argument array
        extract($args);

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

        if (!isset($object) || empty($object->objectid)) {
            return;
        }

        if (!$object->checkAccess('view')) {
            $msg = $this->ml('View #(1) is forbidden', $object->label);
            return $this->ctl()->forbidden($msg);
        }

        // Check if we are filtering
        try {
            $conditions = unserialize($this->session()->getVar('DynamicData.Filter.' . $object->name));
            if (!empty($conditions)) {
                $object->dataquery->addconditions($conditions);
            }
        } catch (Exception $e) {
        }

        // Pass back the relevant variables to the template if necessary
        $data = $object->toArray();

        // Count the number of items matching the preset arguments - do this before getItems()
        $object->countItems();

        // Get the selected items using the preset arguments
        $object->getItems();

        // Pass the object list to the template
        $data['object'] = $object;
        $data['context'] = $object->getContext();

        // TODO: another stray
        $data['catid'] = $catid;
        // TODO: is this needed?
        $data = array_merge($data, $adminapi->menu());

        if ($this->sec()->checkAccess('AdminDynamicData', 0)) {
            if (!empty($data['table'])) {
                $data['querylink'] = $this->mod()->getURL(
                    'admin',
                    'query',
                    ['table' => $data['table']]
                );
            } elseif (!empty($data['join'])) {
                $data['querylink'] = $this->mod()->getURL(
                    'admin',
                    'query',
                    ['itemid' => $data['objectid'],
                        'join' => $data['join']]
                );
            } else {
                $data['querylink'] = $this->mod()->getURL(
                    'admin',
                    'query',
                    ['itemid' => $data['objectid']]
                );
            }
        }

        $this->tpl()->setPageTitle($this->ml('Manage - View #(1)', $data['label']));

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-view.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-view-' . $data['template'] . '.xt')) {
            return $this->tpl()->module($data['tplmodule'], 'admin', 'view', $data, $data['template']);
        } else {
            return $this->tpl()->module('dynamicdata', 'admin', 'view', $data);
        }
    }
}
