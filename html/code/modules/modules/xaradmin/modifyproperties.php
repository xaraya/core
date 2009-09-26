<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Modify module properties
 *
 * This function queries the database for
 * the module's information
 * and passes the data to the template.
 *
 * @author Xaraya Development Team
 * @param id registered module id
 * @param return_url optional return URL after setting the hooks
 * @returns array
 * @return an array of variables to pass to the template
 */
function modules_admin_modifyproperties($args)
{
    extract($args);

    // xarVarFetch does validation if not explicitly set to be not required
    xarVarFetch('id','id',$id);
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    xarVarFetch('phase', 'pre:trim:str:1', $phase, 'form', XARVAR_NOT_REQUIRED);

    $modInfo = xarModGetInfo($id);
    if (!isset($modInfo)) return;

    $modName     = $modInfo['name'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modName::$id")) return;

    $object = xarMod::apiFunc('base', 'admin', 'getmodulesettings', array('module' => $modName));
    $filesettings = xarMod_getFileInfo($modName);

    $fieldlist = array();
    if ($modInfo['admincapable'] && $filesettings['admin']) {
        $fieldlist[] = 'admin_menu_link';
    }
    if ($modInfo['usercapable'] && $filesettings['user']) {
        $fieldlist[] = 'user_menu_link';
    }

    if (!empty($fieldlist) && $modName != 'modules') {
        $object->setFieldList(join(',', $fieldlist));
        $object->getItem();
    } else {
        $object = null;
    }
    if ($phase == 'update') {
        if (isset($object)) {
            $isvalid = $object->checkInput();
            if ($isvalid) {
                $object->updateItem();
                if (empty($return_url)) {
                    $return_url = xarModURL('modules', 'admin', 'modifyproperties', array('id' => $id));
                }
                xarResponse::Redirect($return_url);
            }
        }
    }

    $displayName = $modInfo['displayname'];
    $data['admincapable'] = $modInfo['admincapable'];
    $data['usercapable'] = $modInfo['usercapable'];
    $data['adminallowed'] = $filesettings['admin'];
    $data['userallowed'] = $filesettings['user'];
    $data['savechangeslabel'] = xarML('Save Changes');
    $data['object'] = $object;
    $data['authid'] = xarSecGenAuthKey('modules');
    $data['id'] = $id;
    $data['displayname'] = $modInfo['displayname'];
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}

?>
