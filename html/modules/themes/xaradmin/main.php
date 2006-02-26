<?php
/**
 * Main themes module function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * main themes module function
 * @return themes_admin_main
 *
 * @author Marty Vance
 */
function themes_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

        xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    // success
    return true;
}

?>