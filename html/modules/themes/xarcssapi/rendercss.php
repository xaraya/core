<?php
/**
 * File: $Id$
 *
 * render css related tags
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
 * Format : <xar:themes-render-css /> without params
 * Typical use in the head section is: <xar:themes-render-css />
 *
 * @author Andy Varganov
 * @param none
 * @returns string
 */ 
function themes_cssapi_rendercss($args)
{
    // return the collected css tags
    return "echo xarTplGetCSS();";
}

?>