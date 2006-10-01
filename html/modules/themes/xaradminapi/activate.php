<?php
/**
 * Activate a theme if it has an active function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Activate a theme if it has an active function, otherwise just set the state to active
 *
 * @author Marty Vance
 * @access public
 * @param regid theme's registered id
 * @returns bool
 * @throws BAD_PARAM
 */
function themes_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $themeInfo = xarThemeGetInfo($regid);

    // Update state of theme
    $res = xarModAPIFunc('themes','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_ACTIVE));

    return true;
}
?>
