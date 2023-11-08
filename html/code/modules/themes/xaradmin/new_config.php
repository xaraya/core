<?php
/**
 * Create a new configuration option
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
sys::import('modules.dynamicdata.class.objects.factory');

function themes_admin_new_config()
{
    if(!xarSecurity::check('AddThemes')) return;

    $data = [];
    if (!xarVar::fetch('confirm',    'bool',   $data['confirm'], false,     xarVar::NOT_REQUIRED)) return;

    $data['object'] = DataObjectFactory::getObject(array('name' => 'themes_configurations'));
    if ($data['confirm']) {
    
        // we only retrieve 'preview' from the input here - the rest is handled by checkInput()
        if(!xarVar::fetch('preview', 'str', $preview,  NULL, xarVar::DONT_SET)) {return;}

        // Check for a valid confirmation key
        if(!xarSec::confirmAuthKey()) return;
        
        // Get the data from the form
        $isvalid = $data['object']->checkInput();
        
        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTpl::module('themes','admin','new_config', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['object']->createItem();
            
            // Jump to the next page
            xarController::redirect(xarController::URL('themes','admin','view_configs'));
            return true;
        }
    }
    return $data;
}
