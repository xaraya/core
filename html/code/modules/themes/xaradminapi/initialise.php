<?php
/**
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
 * Initialise a theme
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 * @param regid registered theme id
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

    $xarinitfilename = xarConfigVars::get(null,'Site.BL.ThemesDirectory') . '/'. $themeInfo['directory']  . '/xartheme.php';
    if (!file_exists($xarinitfilename)) {
        throw new FileNotFounException($xarinitfilename);
    }
    include $xarinitfilename;

//var_dump($themevars);exit;
    if (!empty($themevars)) {
        foreach($themevars as $var => $value){
            $value['prime'] = 1;
            if(!isset($value['name']) || !isset($value['value'])){
                $msg = xarML('Malformed Theme Variable (#(1)).', $var);
                throw new Exception($msg);
            }
            xarThemeSetVar($themeInfo['name'], $value['name'], $value['prime'], $value['value'], $value['description']);
        }
    }
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
