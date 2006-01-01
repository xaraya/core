<?php

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function foo_xartables()
{
    // Initialise table array
    $xartable = array();

//    $foo = xarDBGetSiteTablePrefix() . '_foo';

    // Set the table name
//    $xartable['foo'] = $foo;

    // Return the table information
    return $xartable;
}

?>
