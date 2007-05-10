<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * standard function to modify the configuration parameters
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  the data for template
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    remove at some stage if not used. Created for the move of mod overview var
 *          and never in a release, but this var is not used now due to help system.
*/
function modules_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // Disable the overview pages?
    $data['disableoverview'] = xarModVars::get('modules', 'disableoverview');

    // everything else happens in Template for now
    return $data;
}
?>
