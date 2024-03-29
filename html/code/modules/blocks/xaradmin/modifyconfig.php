<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 *
 * @author John Robeson
 * @author Greg Allan
 */

/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @return boolean|array<mixed>|string|void data array for the template display or output display string if invalid data submitted
 */
function blocks_admin_modifyconfig()
{
    // Security
    if(!xarSecurity::check('AdminBlocks')) return;
    
    $data = [];
    if (!xarVar::fetch('phase',        'str:1:100', $phase,       'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('tab', 'str:1:100', $data['tab'], 'general', xarVar::NOT_REQUIRED)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'blocks'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $noexceptions = (int)xarModVars::get('blocks', 'noexceptions');
            $data['noexceptions'] = (!isset($noexceptions)) ? 1 : $noexceptions;

            $data['exceptionoptions'] = array(
                array('id' => 1, 'name' => xarML('Fail Silently')),
                array('id' => 0, 'name' => xarML('Raise Exception')),
            );
        break;

        case 'update':
            // Confirm authorisation code
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                xarController::getRequest()->msgAjax($data['module_settings']->getInvalids());
                return xarTpl::module('blocks','admin','modifyconfig', $data);
            } else {
                $itemid = $data['module_settings']->updateItem();
                if (!xarVar::fetch('noexceptions', 'int:0:1', $noexceptions, 0, xarVar::NOT_REQUIRED)) return;
                xarModVars::set('blocks', 'noexceptions', $noexceptions);
            //    xarController::redirect(xarController::URL('blocks', 'admin', 'modifyconfig'));
            //    return true;
            }
            // If this is an AJAX call, end here
            xarController::getRequest()->exitAjax();
            xarController::redirect(xarServer::getCurrentURL());
            return true;
    }
    return $data;
}
