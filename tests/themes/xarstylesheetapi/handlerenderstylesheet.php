<?php
/**
 * File: $Id$
 *
 * Handle render StyleSheet form field tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author Marty Vance
 * @todo none
 */

/**
 * Handle <xar:themes-include-stylesheet ...> form field tags
 * Format : <xar:themes-render-stylesheet />
 * Typical use in the head section is: <xar:themes-render-stylesheet />
 * This tag is only needed once per page request
 *
 * @author Marty Vance
 * @returns string
 * @return empty string
 */ 
function themes_stylesheetapi_handlerenderstylesheet()
{
    // Send the CSS through the template to display.
    $out =  "echo trim(xarTplModule('themes',
                                   'stylesheet',
                                   'render',
                                   array('styles' => \$GLOBALS['xarTpl_additionalStyles'])
                      )
                  );";
xarLogMessage('Rendering CSS from file list.... styles:' . count($GLOBALS['xarTpl_additionalStyles']));
return $out;
}

?>
