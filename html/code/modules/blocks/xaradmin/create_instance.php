<?php
/**
 * Block management - create a new block instance
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
 /**
 * create a new block instance
 * @author Jim McDonald
 * @author Paul Rosania
 */
function blocks_admin_create_instance()
{
    // Get parameters
    if (!xarVarFetch('block_type', 'str:1:', $type, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:passthru:str:1:', $name, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_title', 'str:1:', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_state', 'int:0:2', $state, 0, XARVAR_NOT_REQUIRED)) {return;}

    if (!xarVarFetch('block_groups', 'array', $groups, array(), XARVAR_NOT_REQUIRED)) {return;}
    //if (!xarVarFetch('block_template', 'str:1:', $template, null, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_template_inner', 'str:1:', $template_inner, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_template_outer', 'str:1:', $template_outer, NULL, XARVAR_NOT_REQUIRED)) return;

    if (empty($name)) return xarTplModule('blocks','user','errors',array('layout' => 'missing_name'));
    
    // Security
    if(!xarSecurityCheck('AddBlocks', 0, 'Instance')) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }

    // Check if block name has already been used.
    $checkname = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name));
    if (!empty($checkname)) {
        throw new DuplicateException(array('block',$name));
    }

    $template = $template_inner;
    if (!empty($template_outer)) $template .= ';' . $template_inner;

    // Pass to API
    $bid = xarMod::apiFunc(
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
    xarController::redirect(
        xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid))
    );

    return true;
}

?>
