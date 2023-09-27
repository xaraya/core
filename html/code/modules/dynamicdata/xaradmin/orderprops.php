<?php
/**
 * Update the dynamic properties for a module + itemtype
 *
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
 * Re-order the dynamic properties for a module + itemtype
 *
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @return boolean|void true on success and redirect to modifyprop
 */
function dynamicdata_admin_orderprops()
{
    // Security
    if(!xarSecurity::check('EditDynamicData')) {
        return;
    }

    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVar::fetch()
    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'int:1:', $itemtype, 0, xarVar::DONT_SET)) {
        return;
    }

    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('direction', 'isset', $direction, null, xarVar::DONT_SET)) {
        return;
    }

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

    if (!xarSec::confirmAuthKey()) {
        //return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }

    $objectinfo = DataObjectMaster::getObjectInfo(
        [
                                    'objectid' => $objectid,
                                    ]
    );

    $objectid = $objectinfo['objectid'];
    $module_id = $objectinfo['moduleid'];
    $itemtype = $objectinfo['itemtype'];

    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = ['module id', 'admin', 'updateprop', 'dynamicdata'];
        throw new BadParameterException($vars, $msg);
    }

    $fields = xarMod::apiFunc(
        'dynamicdata',
        'user',
        'getprop',
        ['objectid' => $objectid,
                                            'module_id' => $module_id,
                                            'itemtype' => $itemtype,
                                         'allprops' => true]
    );
    $orders = [];
    $currentpos = null;
    foreach ($fields as $fname => $field) {
        if ($field['id'] == $itemid) {
            $move_prop = $fname;
            $currentpos = $field['seq'];
        }
        $orders[] = $fname;
    }
    $i = 0;
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
        if (!xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'updateprop',
            ['id' => $itemid,
                                'label' => $fields[$move_prop]['label'],
                                'type' => $fields[$move_prop]['type'],
                                'seq' => $fields[$swapwith]['seq']]
        )) {
            return;
        }

        if (!xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'updateprop',
            ['id' => $fields[$swapwith]['id'],
                                'label' => $fields[$swapwith]['label'],
                                'type' => $fields[$swapwith]['type'],
                                'seq' => $fields[$move_prop]['seq']]
        )) {
            return;
        }
    }

    xarController::redirect(xarController::URL(
        'dynamicdata',
        'admin',
        'modifyprop',
        ['module_id'    => $module_id,
                              'itemtype' => $itemtype,
        ]
    ));


    // Return
    return true;
}
