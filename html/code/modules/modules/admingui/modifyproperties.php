<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin modifyproperties function
 * @extends MethodClass<AdminGui>
 */
class ModifypropertiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify module properties
     * This function queries the database for
     * the module's information
     * and passes the data to the template.
     * @author Xaraya Development Team
     * @param array $args
     * with
     *     int id registered module id
     *     string return_url optional return URL after setting the hooks
     * @return array|string|void data for the template display
     * @see AdminGui::modifyproperties()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // xarVar::fetch does validation if not explicitly set to be not required
        xarVar::fetch('id', 'int', $id, 0, xarVar::NOT_REQUIRED);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }

        xarVar::fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET);
        xarVar::fetch('phase', 'pre:trim:str:1', $phase, 'form', xarVar::NOT_REQUIRED);

        $modInfo = xarMod::getInfo($id);
        if (!isset($modInfo)) {
            return;
        }

        $modName     = $modInfo['name'];

        // Security
        if (!xarSecurity::check('AdminModules', 0, 'All', "$modName::$id")) {
            return;
        }

        $object = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => $modName]);
        $filesettings = xarMod::getFileInfo($modName);

        $fieldlist = [];
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
                        $return_url = xarController::URL('modules', 'admin', 'modifyproperties', ['id' => $id]);
                    }
                    xarController::redirect($return_url, null, $this->getContext());
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
}
