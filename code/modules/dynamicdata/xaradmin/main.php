<?php
/**
 * Main administrative function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * the main administration function
 *
 */
function dynamicdata_admin_main()
{
    if(!xarSecurityCheck('EditDynamicData')) return;

    if (xarModVars::get('modules', 'disableoverview') == 0){
        return xarTplModule('dynamicdata','admin','overview');
    } else {
        xarResponse::Redirect(xarModURL('dynamicdata', 'admin', 'view'));
        return true;
    }
}

?>