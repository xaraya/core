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
function themes_cssapi_rendercss($args)
{
    $args['method'] = 'render';
    $args['base'] = 'theme';
    return xarModAPIFunc('themes', 'user', 'handlecsstags', $args);
}

?>