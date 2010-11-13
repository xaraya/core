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
 * Update theme information
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 * @param $args['regid'] the id number of the theme to update
 * @param $args['displayname'] the new display name of the theme
 * @param $args['description'] the new description of the theme
 * @return boolean true on success, false on failure
 */
function themes_adminapi_update(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    if (!isset($updatevars)) throw new EmptyParameterException('updatevars');

    // Security Check
    if (!xarSecurityCheck('AdminThemes',0,'All',"All:All:$regId")) return;

    // Get theme name
    $themeInfo = xarThemeGetInfo($regid);
    $themename = $themeInfo['name'];

    foreach($updatevars as $uvar){
        $updated = xarThemeSetVar($themename, $uvar['name'], $uvar['prime'], $uvar['value'], $uvar['description']);
        if (!isset($updatevars)) {
            $msg = xarML('Unable to update #(1) variable #(2)).', $themename, $uvar['name']);
            throw new Exception($msg);
        }

    }

    return true;
}

?>