<?php
/**
 * File: $Id$
 *
 * List modules nad current settings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 */
function blocks_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}

    xarModSetVar('blocks', 'selstyle', $selstyle);

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances',array('filter' => $filter)));

    return true;
}

?>