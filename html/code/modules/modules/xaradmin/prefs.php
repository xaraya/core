<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Set preferences for modules module
 *
 * @author Xaraya Development Team
 * @access public
 * @param none
 * @returns array
 * @todo 
 */
function modules_admin_prefs()
{
    
    // Security check
    if(!xarSecurityCheck('AdminModules')) return;
    
    $data = array();
    
    // done
    return $data;
}

?>
