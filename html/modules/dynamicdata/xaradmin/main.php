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
// Security Check
    if(!xarSecurityCheck('EditDynamicData')) return;

    if (xarModGetVar('modules', 'disableoverview') == 0){
        $data = xarModAPIFunc('dynamicdata','admin','menu');

        // Return the template variables defined in this function
        return $data;
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));
    }

    return true;
}

?>
