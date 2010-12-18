<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 *
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  mixed data array for the template display or output display string if invalid data submitted
*/
function modules_admin_modifyconfig()
{
    // Security
    if(!xarSecurityCheck('AdminModules')) return;
    
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if(!xarVarFetch('disableoverview','checkbox', $data['disableoverview'], (bool)xarModVars::get('modules', 'disableoverview'), XARVAR_NOT_REQUIRED)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'modules'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:
        break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                return xarTplModule('modules','admin','modifyconfig', $data);        
            } else {
                $itemid = $data['module_settings']->updateItem();
            }
            xarModVars::set('modules', 'disableoverview', $data['disableoverview']);
        break;
    }
    return $data;
}
?>
