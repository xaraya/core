<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

    /**
     * Function to modify admin configuration
     * 
     * @param void N/A
     * @return array|boolean Returns display data array or true on success, null on failure.
     */
    function categories_admin_modifyconfig()
    {
        // Security Check
        if (!xarSecurityCheck('AdminCategories')) return;
        if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('tabmodule', 'str:1:100', $tabmodule, 'categories', XARVAR_NOT_REQUIRED)) return;

        $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'categories'));
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();

        $regid = xarMod::getRegID($tabmodule);
        switch (strtolower($phase)) {
            case 'modify':
            default:
                switch ($data['tab']) {
                    case 'general':
                        break;
                    case 'categories_hooks':
                        break;
                    default:
                        break;
                }

                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSecConfirmAuthKey()) {
                    return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                }        
                if (!xarVarFetch('usejsdisplay', 'checkbox', $usejsdisplay, xarModVars::get('categories', 'usejsdisplay'), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('numstats', 'int', $numstats, xarModVars::get('categories', 'numstats'), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('showtitle', 'checkbox', $showtitle, xarModVars::get('categories', 'showtitle'), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('allowbatch', 'checkbox', $allowbatch, xarModVars::get('categories', 'allowbatch'), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('categoriesobject', 'str', $categoriesobject, xarModVars::get('categories', 'categoriesobject'), XARVAR_NOT_REQUIRED)) return;

                $modvars = array(
                                'usejsdisplay',
                                'numstats',
                                'showtitle',
                                'allowbatch',
                                'categoriesobject',
                                );

                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    return xarTplModule('categories','admin','modifyconfig', $data);        
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                xarController::redirect(xarModURL('categories', 'admin', 'modifyconfig',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                // Return
                return true;
                break;

        }
        $data['tabmodule'] = $tabmodule;
        return $data;
    }
?>