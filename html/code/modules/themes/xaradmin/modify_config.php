<?php
/**
 * Modify a configuration option
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
    
    function themes_admin_modify_config()
    {
        if (!xarSecurityCheck('EditThemes')) return;

        if (!xarVarFetch('itemid' ,    'int',    $data['itemid'] , 0 ,          XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('update',    'str',   $data['update'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_configurations'));
        $data['object']->getItem(array('itemid' => $data['itemid']));

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if(!xarSecConfirmAuthKey()) return;

            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTpl::module('themes','admin','modify_config', $data);
            } else {

                // Good data: create the item
                $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));
                if ($data['update']) {
                    xarController::redirect(xarModURL('themes','admin','view_configs'));
                    return true;
                } else {
                    xarController::redirect(xarModURL('themes','admin','modify_config', $data));
                    return true;
                }
            }
        }
        return $data;
    }
?>