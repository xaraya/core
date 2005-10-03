<?php
/**
 * Handle css tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/*
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