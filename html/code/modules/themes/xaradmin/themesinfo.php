<?php
/**
 * View complete theme information/details
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
 * View complete theme information/details
 * function passes the data to the template
 *
 * @author Marty Vance
 * @access public
 * @param none
 * @returns array
 * @todo some facelift
 */
function themes_admin_themesinfo()
{
    $data = array();
    
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return; 
    if (empty($id)) return xarResponse::notFound();

    // obtain maximum information about a theme
    $info = xarThemeGetInfo($id);
    // data vars for template
    $data['themeid']              = xarVarPrepForDisplay($id);
    $data['themename']            = xarVarPrepForDisplay($info['name']);
    $data['themedescr']           = xarVarPrepForDisplay($info['description']);
    //$data['themedispname']        = xarVarPrepForDisplay($themeinfo['displayname']);
    $data['themelisturl']         = xarModURL('themes', 'admin', 'list');

    $data['themedir']             = xarVarPrepForDisplay($info['directory']);
    $data['themeclass']           = xarVarPrepForDisplay($info['class']);
    $data['themever']             = xarVarPrepForDisplay($info['version']);
    $data['themestate']           = $info['state'];
    $data['themeauthor']          = preg_replace('/,/', '<br />', xarVarPrepForDisplay($info['author']));
    if(!empty($info['dependency'])){
        $dependency             = xarML('Working on it...');
    } else {
        $dependency             = xarML('None');
    }
    $data['themedependency']      = xarVarPrepForDisplay($dependency);
    
    // Redirect
    return $data;
}
?>
