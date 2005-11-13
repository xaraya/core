<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Installs a theme
 *
 * Loads module themes API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
 *
 * @param id the module id to initialise
 * @returns
 * @return
 */
function themes_admin_install()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;
    if (!xarVarFetch('id', 'int:1:', $id)) return;
    $minfo=xarThemeGetInfo($id);
    if (!xarModAPIFunc('themes','admin','install',array('regid'=>$id))) return;

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>
