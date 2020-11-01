<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * 
 */

/**
 * Delete category links of module items.
 * 
 * @param void N/A
 * @return boolean|null Returns true on success, null on failure.
 */
function categories_admin_unlink()
{ 
    // Security Check
    if(!xarSecurity::check('AdminCategories')) return;

    if(!xarVar::fetch('modid',    'isset', $modid,     NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemtype', 'isset', $itemtype,  NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemid',   'isset', $itemid,    NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('catid',    'isset', $catid,     NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) return; 

    // Check for confirmation.
    if (empty($confirm)) {
        $data = array();
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;

        $what = '';
        if (!empty($modid)) {
            $modinfo = xarMod::getInfo($modid);
            if (empty($itemtype)) {
                $data['modname'] = ucwords($modinfo['displayname']);
            } else {
                // Get the list of all item types for this module (if any)
                $mytypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                                         // don't throw an exception if this function doesn't exist
                                         array(), 0);
                if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                } else {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                }
            }
        }
        $data['confirmbutton'] = xarML('Confirm'); 
        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSec::genAuthKey(); 
        // Return the template variables defined in this function
        return $data;
    } 

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // unlink API does not support deleting all category links for all modules
    if (!empty($modid)) {
        $modinfo = xarMod::getInfo($modid);
        if (!xarMod::apiFunc('categories','admin','unlink',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'iid' => $itemid,
                                 'confirm' => $confirm))) {
            return;
        }
        // TODO: support deleting all links for a category too (cfr. checklinks)
    }
    xarController::redirect(xarController::URL('categories', 'admin', 'stats'));
    return true;
}

?>
