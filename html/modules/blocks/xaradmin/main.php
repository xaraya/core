<?php
/**
 * Block Functions
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
 * Blocks Functions
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_main()
{
    if(!xarSecurityCheck('EditBlock')) return;

    if (xarModVars::get('modules', 'disableoverview') == 0){
        return xarTplModule('blocks','admin','overview');
    } else {
        xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));
        return true;
    }
}

?>