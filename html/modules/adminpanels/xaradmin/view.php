<?php
/**
 * File: $Id
 *
 * Standard Admin Overview
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/
/**
 * standard admin overview
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  data for template or void on failure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
*/
function adminpanels_admin_view()
{

    // Security Check
    if(!xarSecurityCheck('EditPanel')) return;

    // TODO: prepare the overview based on what is configured by config
    $data = array();

    // push data to template
    return $data;
}

?>