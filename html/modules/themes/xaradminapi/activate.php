<?php
/**
 * File: $Id$
 *
 * Activate a theme
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
 * Activate a theme if it has an active function, otherwise just set the state to active
 *
 * @access public
 * @param regid theme's registered id
 * @returns bool
 * @raise BAD_PARAM
 */
function themes_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return NULL;
    }


    // Update state of theme
    $res = xarModAPIFunc('themes',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_ACTIVE));
    if (!isset($res) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    return true;
}
?>