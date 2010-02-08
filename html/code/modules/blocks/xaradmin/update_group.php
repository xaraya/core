<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * update a block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_update_group()
{
    // Get parameters
    if (!xarVarFetch('id', 'int:1:', $id)) {return;}
    if (!xarVarFetch('authid', 'str:1:', $authid)) {return;}
    if (!xarVarFetch('group_name', 'pre:lower:ftoken:field:Group Name:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('group_template', 'pre:trim:lower:ftoken', $template, null, XARVAR_NOT_REQUIRED)) {return;}

    sys::import('modules.dynamicdata.class.properties.master');
    $orderselect = DataPropertyMaster::getProperty(array('name' => 'orderselect'));
    $orderselect->checkInput('group_instance_order');

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Security Check
    if(!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}

    // Explode the instance order from id1;id2;etc to an array
    $group_instance_order = $orderselect->order;
    if (!empty($group_instance_order)) {
        $group_instance_order = explode(';', $group_instance_order);
    } else {
        $group_instance_order = array();
    }

    // Get the current group.
    $currentgroup = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('id' => $id));
    if (empty($currentgroup)) {return;}

    // If the name is being changed, then check the new name has not already been used.
    if ($currentgroup['name'] != $name) {
        $checkname = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('name' => $name));
        if (!empty($checkname)) {
            throw new DuplicateException(array('block group',$name));
        }
    }

    // Pass to API
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'update_group',
        array(
            'id' => $id,
            'template' => $template,
            'name' => $name,
            'instance_order' => $group_instance_order)
        )
    ) {return;}

    xarController::redirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>
