<?php
/**
 * Utilities
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 */
 /*
 * @author John Cox <niceguyeddie@xaraya.com>
 */
function dynamicdata_admin_utilities($args)
{
    // Security check
    if (!xarSecurityCheck('EditDynamicData')) return;
    extract($args);
    if(!xarVarFetch('q','str', $data['option'], 'query', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    return $data;
}
?>