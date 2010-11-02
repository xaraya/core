<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @subpackage modules module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/1.html
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * standard function to modify the configuration parameters
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  the data for template
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    remove at some stage if not used. Created for the move of mod overview var
 *          and never in a release, but this var is not used now due to help system.
*/
function modules_admin_modifyconfig()
{
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