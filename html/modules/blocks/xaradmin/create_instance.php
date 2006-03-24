<?php
/**
 * Block management - create a new block instance
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
 /**
 * create a new block instance
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_create_instance()
{
    // Get parameters
    if (!xarVarFetch('block_type', 'str:1:', $type)) {return;}
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('block_state', 'int:0:2', $state)) {return;}
    if (!xarVarFetch('block_title', 'str:1:', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_template', 'str:1:', $template, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_groups', 'array', $groups, array(), XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Security Check
    if(!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Check if block name has already been used.
    $checkname = xarModAPIFunc('blocks', 'user', 'get', array('name' => $name));
    if (!empty($checkname)) {
        $msg = xarML('Block name "#(1)" already exists', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Pass to API
    $bid = xarModAPIFunc(
        'blocks', 'admin', 'create_instance',
        array(
            'name'      => $name,
            'title'     => $title,
            'type'      => $type,
            'template'  => $template,
            'state'     => $state,
            'groups'    => $groups
        )
    );

    if (!$bid) {return;}

    // Go on and edit the new instance
    xarResponseRedirect(
        xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid))
    );

    return true;
}

?>