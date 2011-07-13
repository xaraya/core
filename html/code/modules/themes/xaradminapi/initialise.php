<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Initialise a theme
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 *        string   $args['regid'] registered theme id
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, THEME_NOT_EXIST
 */
function themes_adminapi_initialise(Array $args=array())
{

    extract($args);

    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo)) {
        throw new ThemeNotFoundException($regid,'Theme (regid: #(1) does not exist.');
    }
    $themename = $themeInfo['name'];
    $themeInfo = xarMod::getBaseInfo($themename, 'theme');

    // Update state of theme
    $set = xarMod::apiFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_INACTIVE));

    if (!isset($set)) {
        throw new Exception('Could not set state of theme');
        xarSession::setVar('errormsg', xarML('Theme state change failed'));
        return false;
    }

    return true;
}
?>
