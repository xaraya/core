<?php
/**
 * File: $Id$
 *
 * compile-time template tag handler
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author Andy Varganov
 * @todo none
 */

/**
 * Format : <xar:additional-styles /> without params
 * Typical use in the head section is: <xar:additional-styles />
 *
 * @author Andy Varganov
 * @param none
 * @returns string
 */
function themes_cssapi_delivercss($args)
{
    $args['method'] = 'render';
    $args['base'] = 'theme';

    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
    $argstring .= ")";
    return "echo xarModAPIFunc('themes', 'user', 'deliver',$argstring);\n";
}

?>