<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 *
 * Blocks System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage blocks module
 * @author Jim McDonald, Paul Rosania
*/

function blocks_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the example item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $userblocks = xarDBGetSiteTablePrefix() . '_userblocks';

    // Set the table name
    $xartable['userblocks'] = $userblocks;

    // Return the table information
    return $xartable;
}

?>
