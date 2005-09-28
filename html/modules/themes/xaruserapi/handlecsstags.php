<?php
/**
 * File: $Id$
 *
 * css related functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
*
 * @subpackage themes module
 * @author andyv <andyv@xaraya.com>
*/

function themes_userapi_handlecsstags($args)
{
    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
        $argstring .= ")";
    if (isset($args['method']) && $args['method'] == 'render') {
        return "echo xarModAPIFunc('themes', 'user', 'deliver',$argstring);\n";
    } else {
        return "xarModAPIFunc('themes', 'user', 'register',$argstring);\n";
    }
}


?>