<?php
/**
 * Update the dynamic properties for a module + itemtype
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Re-order the dynamic properties for a module + itemtype
 *
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @throws BAD_PARAM
 * @return bool true on success and redirect to modifyprop
 */
function dynamicdata_admin_orderprops()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarFetch()
    if(!xarVarFetch('objectid',          'isset', $objectid,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',         'isset', $module_id,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',          'int:1:', $itemtype,         0, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('itemid',        'isset', $itemid,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('direction',     'isset', $direction,      NULL, XARVAR_DONT_SET)) {return;}

    if (empty($direction)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('direction', 'admin', 'orderprops', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (empty($itemid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('itemid', 'admin', 'orderprops', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (!xarSecConfirmAuthKey()) {
        //return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }

    $objectinfo = DataObjectMaster::getObjectInfo(
                                    array(
                                    'objectid' => $objectid,
                                    ));

    $objectid = $objectinfo['objectid'];
    $module_id = $objectinfo['moduleid'];
    $itemtype = $objectinfo['itemtype'];

    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module id', 'admin', 'updateprop', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                                   array('objectid' => $objectid,
                                            'module_id' => $module_id,
                                            'itemtype' => $itemtype,
                                         'allprops' => true));
    $orders = array();
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
        if ($field['seq'] == $currentpos && $direction == 'up' && isset($orders[$i-1])) {
            $swapwith = $orders[$i-1];
            $swappos = $i;
            $currentpos = $i+1;
        } elseif ($field['seq'] == $currentpos && $direction == 'down' && isset($orders[$i+1])) {
            $swapwith = $orders[$i+1];
            $swappos = $i;
            $currentpos = $i+1;
        }
        if (isset($swappos)) break;
        $i++;
    }

    if (isset($swappos)) {
        if (!xarMod::apiFunc('dynamicdata','admin','updateprop',
                          array('id' => $itemid,
                                'label' => $fields[$move_prop]['label'],
                                'type' => $fields[$move_prop]['type'],
                                'seq' => $fields[$swapwith]['seq']))) {
            return;
        }

        if (!xarMod::apiFunc('dynamicdata','admin','updateprop',
                          array('id' => $fields[$swapwith]['id'],
                                'label' => $fields[$swapwith]['label'],
                                'type' => $fields[$swapwith]['type'],
                                'seq' => $fields[$move_prop]['seq']))) {
            return;
        }
    }

    xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                        array('module_id'    => $module_id,
                              'itemtype' => $itemtype,
        )));


    // Return
    return true;
}

?>
