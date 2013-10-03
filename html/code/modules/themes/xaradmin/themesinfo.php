<?php
/**
 * View complete theme information/details
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
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
 * @return array data for the template display
 * @todo some facelift
 */
function themes_admin_themesinfo()
{
    // Security
    if (!xarSecurityCheck('EditThemes')) return; 
    
    $data = array();
    
    if (!xarVarFetch('id', 'int:1:', $themeid, 0, XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('exit', 'isset', $exit, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (empty($themeid)) return xarResponse::notFound();

    // obtain maximum information about a theme
    $info = xarThemeGetInfo($themeid);

    // get the theme object corresponding to this theme
    sys::import('modules.dynamicdata.class.objects.master');
    $theme = DataObjectMaster::getObject(array('name'   => 'themes'));
    $id = $theme->getItem(array('itemid' => $info['systemid']));
    if (empty($theme)) return;

    $data['theme'] = $theme;
    $data['themeid'] = $themeid;
    $data['properties'] = $theme->properties;

    if ($confirm || $exit) {
    
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        // Get the data from the form
        $isvalid = $data['theme']->properties['configuration']->checkInput();
        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTpl::module('themes','admin','themesinfo', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['theme']->updateItem(array('itemid' => $info['systemid']));
            
            // Jump to the next page
            if ($exit) {
                xarController::redirect(xarModURL('themes','admin','list'));
            } else {
                xarController::redirect(xarModURL('themes','admin','themesinfo',array('id' => $themeid)));
            }
            return true;
        }
    }

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
    
    return $data;
}
?>
