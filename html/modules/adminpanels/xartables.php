<?php 
/**
 * File: $Id$
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels
 * @author Andy Varganov <andyv@xaraya.com>
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