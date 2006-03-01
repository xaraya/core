<?php

/*
 * xaraya wrapper class for Creole
 *
 *
 */
include_once 'Creole.php';
class xarDB extends Creole 
{
    // Instead of the superglobal, save our connections here.
    public static $connections = array();
}

?>
