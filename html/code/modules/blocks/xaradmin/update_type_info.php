<?php
/**
 * Register a new block type
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
 * Register New Block Type
 * @author Jim McDonald
 * @author Paul Rosania
 */
function blocks_admin_update_type_info()
{
    // Security
    if (!xarSecurityCheck('AdminBlocks', 0, 'Instance')) {return;}

    // Get parameters
    if (!xarVarFetch('modulename', 'str:1:', $modulename, 'base', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('blocktype', 'str:1:', $blocktype, '', XARVAR_NOT_REQUIRED)) {return;}

    xarMod::apiFunc(
        'blocks', 'admin', 'update_type_info',
        array('module' => $modulename, 'type' => $blocktype)
    );

    xarController::redirect(xarModURL('blocks', 'admin', 'view_types'));
    return true;
}

?>
