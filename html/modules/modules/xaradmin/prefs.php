<?php
/**
 * File: $Id$
 *
 * Set preferences for modules module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Set preferences for modules module
 *
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