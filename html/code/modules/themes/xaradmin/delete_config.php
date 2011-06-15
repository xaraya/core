<?php
/**
 * Delete a configuration option
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
sys::import('modules.dynamicdata.class.objects.master');

function themes_admin_delete_config()
{
    if (!xarVarFetch('itemid' ,    'int',    $data['itemid'] , 0 ,          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm',    'int',   $data['confirm'], 0,       XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_configurations'));
    $data['object']->getItem(array('itemid' => $data['itemid']));
    
    // Security
    if (!$data['object']->checkAccess('delete'))
        return xarResponse::Forbidden(xarML('Delete #(1) is forbidden', $data['object']->label));

    if ($data['confirm']) {
    
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        // Delete the item
        $item = $data['object']->deleteItem();
            
        // Jump to the next page
        xarResponse::redirect(xarModURL('themes','admin','view_configs'));
        return true;
    }
    return $data;
}

?>