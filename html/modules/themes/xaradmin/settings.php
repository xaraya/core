<?php
/**
 * File: $Id$
 *
 * List themes and current settings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
/**
 * List themes and current settings
 * @param several params from the associated form in template
 *
 */
function themes_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;
    
    // form parameters
    if (!xarVarFetch('hidecore',  'str:1:', $hidecore,  '0',                  XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selstyle',  'str:1:', $selstyle,  'plain',              XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARTHEME_STATE_ANY', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selsort',   'str:1:', $selsort,   'namedesc',           XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('regen',     'str:1:', $regen,     false,                XARVAR_NOT_REQUIRED)) return;
    
    if (!xarModSetUserVar('themes', 'hidecore', $hidecore)) return;
    if (!xarModSetUserVar('themes', 'selstyle', $selstyle)) return;
    if (!xarModSetUserVar('themes', 'selfilter', $selfilter)) return;
    if (!xarModSetUserVar('themes', 'selsort', $selsort)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('regen' => $regen = 1)));
}

?>