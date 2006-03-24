<?php
/**
 * Regenerate list of available themes
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
 * Regenerate list of available themes
 *
 * Loads theme admin API and calls the regenerate function
 * to actually perform the regeneration, then redirects
 * to the list function with a status meessage and returns true.
 *
 * @author Marty Vance
 * @access public
 * @param none
 * @returns bool
 * @
 */
function themes_admin_regenerate()
{
    // Security check
    if (!xarSecConfirmAuthKey()) return;
    // Regenerate themes
    $regenerated = xarModAPIFunc('themes', 'admin', 'regenerate');

    if (!isset($regenerated)) return;
    // Redirect
    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>