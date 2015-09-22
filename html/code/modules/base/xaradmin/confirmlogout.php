<?php
/**
 * Confirm logout from Admin panels system
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author Andy Varganov <andyv@xaraya.com>
 */

/**
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>

 * @param   void N/A
 * @return  array Data array for display template.
*/
function base_admin_confirmlogout()
{
    // Security
    if(!xarSecurityCheck('EditBase')) return;
    
    // Template does it all
    return array();
}
?>
