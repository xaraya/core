<?php
/**
 * File: $Id$
 *
 * Administration System
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
 * specifies module tables namees
 *
 * @author  Andy Varganov <andyv@yaraya.com>
 * @access  public
 * @param   none
 * @return  $xartable array
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the example item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $menutable = xarDBGetSiteTablePrefix() . '_admin_menu';
    $wctable = xarDBGetSiteTablePrefix() . '_admin_wc';

    // Set the table name
    $xartable['admin_menu'] = $menutable;
    $xartable['waiting_content'] = $wctable;

    // Return the table information
    return $xartable;
}

?>