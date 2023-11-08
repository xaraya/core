<?php
/**
 * Delete a configuration option
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

function themes_admin_delete_config()
{
    $data = [];
    if (!xarVar::fetch('itemid' ,    'int',    $data['itemid'] , 0 ,          xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('confirm',    'int',   $data['confirm'], 0,       xarVar::NOT_REQUIRED)) return;

    $data['object'] = DataObjectFactory::getObject(array('name' => 'themes_configurations'));
    $data['object']->getItem(array('itemid' => $data['itemid']));
    
    // Security
    if (!$data['object']->checkAccess('delete'))
        return xarResponse::Forbidden(xarML('Delete #(1) is forbidden', $data['object']->label));

    if ($data['confirm']) {
    
        // Check for a valid confirmation key
        if(!xarSec::confirmAuthKey()) return;

        // Delete the item
        $item = $data['object']->deleteItem();
            
        // Jump to the next page
        xarResponse::redirect(xarController::URL('themes','admin','view_configs'));
        return true;
    }
    return $data;
}
