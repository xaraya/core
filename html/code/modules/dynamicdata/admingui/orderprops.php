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
use Xaraya\Modules\DynamicData\UserApi;
use Xaraya\Modules\DynamicData\AdminApi;
use BadParameterException;

/**
 * dynamicdata admin orderprops function
 * @extends MethodClass<AdminGui>
 */
class OrderpropsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Re-order the dynamic properties for a module + itemtype
     * @return bool|void true on success and redirect to modifyprop
     * @see AdminGui::orderprops()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        // Get parameters from whatever input we need.  All arguments to this
        // function should be obtained from $this->var()->fetch()
        $this->var()->check('objectid', $objectid);
        $this->var()->check('module_id', $module_id);
        $this->var()->check('itemtype', $itemtype, 'int:1:', 0);

        $this->var()->check('itemid', $itemid);
        $this->var()->check('direction', $direction);

        if (empty($direction)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['direction', 'admin', 'orderprops', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['itemid', 'admin', 'orderprops', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        if (!$this->sec()->confirmAuthKey()) {
            //return $this->ctl()->badRequest('bad_author');
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['authid', 'admin', 'orderprops', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        $objectinfo = $this->data()->getObjectInfo(
            ['objectid' => $objectid]
        );

        $objectid = $objectinfo['objectid'];
        $module_id = $objectinfo['moduleid'];
        $itemtype = $objectinfo['itemtype'];

        if (empty($module_id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['module id', 'admin', 'updateprop', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        $fields = $userapi->getprop(
            ['objectid' => $objectid,
                'module_id' => $module_id,
                'itemtype' => $itemtype,
                'allprops' => true]
        );
        $orders = [];
        $currentpos = null;
        $move_prop = '';
        foreach ($fields as $fname => $field) {
            if ($field['id'] == $itemid) {
                $move_prop = $fname;
                $currentpos = $field['seq'];
            }
            $orders[] = $fname;
        }
        $i = 0;
        $swappos = null;
        $swapwith = '';
        foreach ($fields as $name => $field) {
            if ($field['seq'] == $currentpos && $direction == 'up' && isset($orders[$i - 1])) {
                $swapwith = $orders[$i - 1];
                $swappos = $i;
                $currentpos = $i + 1;
            } elseif ($field['seq'] == $currentpos && $direction == 'down' && isset($orders[$i + 1])) {
                $swapwith = $orders[$i + 1];
                $swappos = $i;
                $currentpos = $i + 1;
            }
            if (isset($swappos)) {
                break;
            }
            $i++;
        }

        if (isset($swappos)) {
            if (!$adminapi->updateprop(
                ['id' => $itemid,
                    'label' => $fields[$move_prop]['label'],
                    'type' => $fields[$move_prop]['type'],
                    'seq' => $fields[$swapwith]['seq']]
            )) {
                return;
            }

            if (!$adminapi->updateprop(
                ['id' => $fields[$swapwith]['id'],
                    'label' => $fields[$swapwith]['label'],
                    'type' => $fields[$swapwith]['type'],
                    'seq' => $fields[$move_prop]['seq']]
            )) {
                return;
            }
        }

        $this->ctl()->redirect($this->mod()->getURL(
            'admin',
            'modifyprop',
            ['module_id'    => $module_id,
                'itemtype' => $itemtype]
        ));


        // Return
        return true;
    }
}
