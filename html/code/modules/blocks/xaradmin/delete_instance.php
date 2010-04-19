<?php
/**
 * Block management - delete a block
 * @package modules
 * @copyright see the html/credits.html file in this release
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
    if (!xarVarFetch('bid', 'id', $bid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) {return;}

    if (empty($bid)) return xarResponse::notFound();

    // Security Check
    if (!xarSecurityCheck('ManageBlocks', 0, 'Instance')) {return;}

    // Get details on current block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('bid' => $bid));

    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array('module' => $blockinfo['module'], 'type' => $blockinfo['type'], 'func' => 'delete'))) return;

    // cascading block files - order is method specific, admin specific, block specific
    $to_check = array();
    $to_check[] = ucfirst($blockinfo['type']) . 'BlockDelete';   // from eg menu_delete.php
    $to_check[] = ucfirst($blockinfo['type']) . 'BlockAdmin';    // from eg menu_admin.php
    $to_check[] = ucfirst($blockinfo['type']) . 'Block';         // from eg menu.php
    foreach ($to_check as $className) {
        // @FIXME: class name should be unique
        if (class_exists($className)) {
            // instantiate the block instance using the first class we find
            $block = new $className($blockinfo);
            break;
        }
    }
    // make sure we instantiated a block,
    if (empty($block)) {
        // return classname not found (this is always class [$type]Block)
        throw new ClassNotFoundException($className);
    }

    if (!$block->checkAccess('delete')) {
        return xarTplModule('privileges','user','errors',array('layout' => 'no_block_privileges'));
    }

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
    }

    // call the blocks own delete method first
    if (!$block->delete()) return;

    // Pass to API
    xarMod::apiFunc(
        'blocks', 'admin', 'delete_instance',
        array('bid' => $bid)
    );

    xarController::redirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>
