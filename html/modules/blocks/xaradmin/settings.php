<?php
/**
 * List modules and current settings
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}

    xarModVars::set('blocks', 'selstyle', $selstyle);

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances',array('filter' => $filter)));

    return true;
}

?>
