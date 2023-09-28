<?php
/**
 * Activate a theme if it has an active function
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * Activate a theme if it has an active function, otherwise just set the state to active
 *
 * @author Marty Vance
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['regid'] theme's registered id
 *        string   $args['name'] theme's name
 * @return boolean true on success, false on failure
 * @throws EmptyParameterException
 */
function themes_adminapi_activate(Array $args=array())
{
    extract($args);

    // Argument check
    if (isset($name)) $regid = xarMod::getRegid($name, 'theme');
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $themeInfo = xarTheme::getInfo($regid);

    // Update state of theme
    $res = xarMod::apiFunc('themes','admin','setstate',
                        array('regid' => $regid,
                              'state' => xarTheme::STATE_ACTIVE));

    return true;
}
