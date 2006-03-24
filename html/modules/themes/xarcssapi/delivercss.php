<?php
/**
 * Handle additional styles tag
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
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