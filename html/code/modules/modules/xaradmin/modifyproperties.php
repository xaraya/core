<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Modify module properties
 *
 * This function queries the database for
 * the module's information
 * and passes the data to the template.
 *
 * @author Xaraya Development Team
 * @param int id registered module id
 * @param string return_url optional return URL after setting the hooks
 * @return array|string|void data for the template display
 */
function modules_admin_modifyproperties(Array $args=array())
{
    extract($args);

    // xarVar::fetch does validation if not explicitly set to be not required
    if (!xarVar::fetch('id', 'int', $id, 0, xarVar::NOT_REQUIRED)) return; 
    if (empty($id)) return xarResponse::notFound();

    xarVar::fetch('return_url', 'isset', $return_url, NULL, xarVar::DONT_SET);
    xarVar::fetch('phase', 'pre:trim:str:1', $phase, 'form', xarVar::NOT_REQUIRED);

    $modInfo = xarMod::getInfo($id);
    if (!isset($modInfo)) return;

    $modName     = $modInfo['name'];

    // Security
    if(!xarSecurity::check('AdminModules',0,'All',"$modName::$id")) return;

    $object = xarMod::apiFunc('base', 'admin', 'getmodulesettings', array('module' => $modName));
    $filesettings = xarMod::getFileInfo($modName);

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
                    $return_url = xarController::URL('modules', 'admin', 'modifyproperties', array('id' => $id));
                }
                xarController::redirect($return_url);
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
    $data['authid'] = xarSec::genAuthKey('modules');
    $data['id'] = $id;
    $data['displayname'] = $modInfo['displayname'];
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}
