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
     * Function to modify admin configuration
     * 
     * @return mixed Returns display data array or true on success, null on failure.
     */
    function categories_admin_modifyconfig()
    {
        // Security Check
        if (!xarSecurity::check('AdminCategories')) return;
        $data = [];
        if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
        if (!xarVar::fetch('tab', 'str:1:100', $data['tab'], 'general', xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('tabmodule', 'str:1:100', $tabmodule, 'categories', xarVar::NOT_REQUIRED)) return;

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
                if (!xarSec::confirmAuthKey()) {
                    return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
                }        
                if (!xarVar::fetch('usejsdisplay', 'checkbox', $usejsdisplay, xarModVars::get('categories', 'usejsdisplay'), xarVar::NOT_REQUIRED)) return;
                if (!xarVar::fetch('numstats', 'int', $numstats, xarModVars::get('categories', 'numstats'), xarVar::NOT_REQUIRED)) return;
                if (!xarVar::fetch('showtitle', 'checkbox', $showtitle, xarModVars::get('categories', 'showtitle'), xarVar::NOT_REQUIRED)) return;
                if (!xarVar::fetch('allowbatch', 'checkbox', $allowbatch, xarModVars::get('categories', 'allowbatch'), xarVar::NOT_REQUIRED)) return;
                if (!xarVar::fetch('categoriesobject', 'str', $categoriesobject, xarModVars::get('categories', 'categoriesobject'), xarVar::NOT_REQUIRED)) return;

                $modvars = array(
                                'usejsdisplay',
                                'numstats',
                                'showtitle',
                                'allowbatch',
                                'categoriesobject',
                                );

                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    return xarTpl::module('categories','admin','modifyconfig', $data);        
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                xarController::redirect(xarController::URL('categories', 'admin', 'modifyconfig',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                // Return
                return true;

        }
        $data['tabmodule'] = $tabmodule;
        return $data;
    }
