<?php
/**
 * Initialise a theme
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Initialise a theme
 *
 * @author Marty Vance
 * @param regid registered theme id
 * @returns bool
 * @return
 * @raise BAD_PARAM, THEME_NOT_EXIST
 */
function themes_adminapi_initialise($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
       $msg = xarML('Missing theme regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));return;
    }

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'THEME_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Theme (regid: $regid) does not exist."));
                       return;
    }

    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    // Include theme initialisation file
    //FIXME: added theme File not exist exception

    // Theme activate function

    // pnAPI compatibility
    // jojodee, fix hard coded themes dir
    $xarinitfilename = xarConfigGetVar('Site.BL.ThemesDirectory').'/'. $themeInfo['directory'] .'/xartheme.php';
    if (!file_exists($xarinitfilename)) {
        $xarinitfilename = xarConfigGetVar('Site.BL.ThemesDirectory').'/'. $themeInfo['directory'] .'/theme.php';
    }
    @include $xarinitfilename;

    if (!empty($themevars)) {
        foreach($themevars as $var => $value){
            $value['prime'] = 1;
            if(!isset($value['name']) || !isset($value['value'])){
                $msg = xarML('Malformed Theme Variable (#(1)).', $var);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $set = xarThemeSetVar($themeInfo['name'], $value['name'], $value['prime'], $value['value'], $value['description']);
            if(!$set) return;
        }
    }
    // Update state of theme
    $set = xarModAPIFunc('themes',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_INACTIVE));
    //die(var_dump($set));
    if (!isset($set)) {
        xarSessionSetVar('errormsg', xarML('Theme state change failed'));
        return false;
    }

    // Success
    return true;
}
?>
