<?php
/**
 * Main administrative function
 *
 * @package modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * the main administration function
 *
 */
function dynamicdata_admin_main()
{
// Security Check
    if(!xarSecurityCheck('EditDynamicData')) return;

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));

    return true;
}

?>