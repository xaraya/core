<?php
/**
 * Confirm logout from Admin panels system
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  array|void data for the template display
*/
function modules_admin_confirmlogout()
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
    // Template does it all
    return array();    
}
