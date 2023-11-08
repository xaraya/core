<?php
/**
 * Modify a configuration option
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
    
    function themes_admin_modify_config()
    {
        if (!xarSecurity::check('EditThemes')) return;

        $data = [];
        if (!xarVar::fetch('itemid' ,    'int',    $data['itemid'] , 0 ,          xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('confirm',    'bool',   $data['confirm'], false,       xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('update',    'str',   $data['update'], false,       xarVar::NOT_REQUIRED)) return;

        $data['object'] = DataObjectFactory::getObject(array('name' => 'themes_configurations'));
        $data['object']->getItem(array('itemid' => $data['itemid']));

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if(!xarSec::confirmAuthKey()) return;

            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTpl::module('themes','admin','modify_config', $data);
            } else {

                // Good data: create the item
                $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));
                if ($data['update']) {
                    xarController::redirect(xarController::URL('themes','admin','view_configs'));
                    return true;
                } else {
                    xarController::redirect(xarController::URL('themes','admin','modify_config', $data));
                    return true;
                }
            }
        }
        return $data;
    }
