<?php
/**
 * Activate a theme if it has an active function
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Activate a theme if it has an active function, otherwise just set the state to active
 *
 * @author Marty Vance
 * @access public
 * @param regid theme's registered id
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function themes_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $themeInfo = xarThemeGetInfo($regid);

    // Update state of theme
    $res = xarMod::apiFunc('themes','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_ACTIVE));

    return true;
}
?>
