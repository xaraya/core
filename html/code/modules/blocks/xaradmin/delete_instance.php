<?php
/**
 * Block management - delete a block 
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * delete a block instance
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_delete_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'id', $bid)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) {return;}

    // Security Check
    if (!xarSecurityCheck('DeleteBlock', 0, 'Instance')) {return;}

    // Get details on current block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('bid' => $bid));

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        return array(
            'instance' => $blockinfo,
            'authid' => xarSecGenAuthKey(),
            'deletelabel' => xarML('Delete')
        );
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    sys::import('modules.dynamicdata.class.properties.master');
    $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
    $exploded = unserialize($blockinfo['content']);
    if (isset($exploded['delete_access'])) {
        $args = array(
            'component' => 'Block',
            'instance' => "All:All:" . $bid,
            'group' => $exploded['delete_access']['group'],
            'level' => $exploded['delete_access']['level'],
        );
        if(!$accessproperty->check($args))
            return xarTplModule('privileges','user','errors',array('layout' => 'no_block_privileges'));
    }

    // Pass to API
    xarMod::apiFunc(
        'blocks', 'admin', 'delete_instance',
        array('bid' => $bid)
    );

    xarResponse::redirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>