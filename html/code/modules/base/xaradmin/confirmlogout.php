<?php
/**
 * Confirm logout from Admin panels system
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  array data for template
 * @throws  no exceptions
 * @todo    nothing
*/
function base_admin_confirmlogout()
{
    // Security
    if(!xarSecurityCheck('EditBase')) return;
    
    // Template does it all
    return array();
}
?>