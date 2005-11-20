<?php
/**
 * Update theme information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Update theme information
 *
 * @author Marty Vance
 * @param $args['regid'] the id number of the theme to update
 * @param $args['displayname'] the new display name of the theme
 * @param $args['description'] the new description of the theme
 * @returns bool
 * @return true on success, false on failure
 */
function themes_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!isset($updatevars)) {
        $msg = xarML('Empty updatevars (#(1)).', $updatevars);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// Security Check
    if (!xarSecurityCheck('AdminTheme',0,'All',"All:All:$regId")) return;

    // Get theme name
    $themeInfo = xarThemeGetInfo($regid);
    $themename = $themeInfo['name'];

    foreach($updatevars as $uvar){
        $updated = xarThemeSetVar($themename, $uvar['name'], $uvar['prime'], $uvar['value'], $uvar['description']);
        if (!isset($updatevars)) {
            $msg = xarML('Unable to update #(1) variable #(2)).', $themename, $uvar['name']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

    }

    return true;
}

?>
