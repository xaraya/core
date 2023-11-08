<?php
/**
 * View complete theme information/details
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
 * View complete theme information/details
 * function passes the data to the template
 *
 * @author Marty Vance
 * @access public
 * @return array<mixed>|string|void data for the template display
 * @todo some facelift
 */
function themes_admin_themesinfo()
{
    // Security
    if (!xarSecurity::check('EditThemes')) return; 
    
    $data = array();
    
    if (!xarVar::fetch('id', 'int:1:', $themeid, 0, xarVar::NOT_REQUIRED)) return; 
    if (!xarVar::fetch('exit', 'isset', $exit, NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('confirm', 'isset', $confirm, NULL, xarVar::DONT_SET)) {return;}
    if (empty($themeid)) return xarResponse::notFound();

    // obtain maximum information about a theme
    $info = xarTheme::getInfo($themeid);

    // get the theme object corresponding to this theme
    sys::import('modules.dynamicdata.class.objects.factory');
    $theme = DataObjectFactory::getObject(array('name'   => 'themes'));
    $id = $theme->getItem(array('itemid' => $info['systemid']));
    if (empty($theme)) return;

    $data['theme'] = $theme;
    $data['themeid'] = $themeid;
    $data['properties'] = $theme->properties;

    if ($confirm || $exit) {
    
        // Check for a valid confirmation key
        if(!xarSec::confirmAuthKey()) return;

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
                xarController::redirect(xarController::URL('themes','admin','view'));
            } else {
                xarController::redirect(xarController::URL('themes','admin','themesinfo',array('id' => $themeid)));
            }
            return true;
        }
    }

    $data['themename']            = xarVar::prepForDisplay($info['name']);
    $data['themedescr']           = xarVar::prepForDisplay($info['description']);
    //$data['themedispname']        = xarVar::prepForDisplay($themeinfo['displayname']);
    $data['themelisturl']         = xarController::URL('themes', 'admin', 'view');

    $data['themedir']             = xarVar::prepForDisplay($info['directory']);
    $data['themeclass']           = xarVar::prepForDisplay($info['class']);
    $data['themever']             = xarVar::prepForDisplay($info['version']);
    $data['themestate']           = $info['state'];
    $data['themeauthor']          = preg_replace('/,/', '<br />', xarVar::prepForDisplay($info['author']));
    if(!empty($info['dependency'])){
        $dependency             = xarML('Working on it...');
    } else {
        $dependency             = xarML('None');
    }
    $data['themedependency']      = xarVar::prepForDisplay($dependency);
    
    return $data;
}
