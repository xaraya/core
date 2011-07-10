<?php
/**
 * Confirm logout from Admin panels system
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 *
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  array data for the template display
*/
function modules_admin_confirmlogout()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
    // Template does it all
    return array();    
}
?>
