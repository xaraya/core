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
        $this->var()->find('id', $id, 'int', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }

        $this->var()->check('return_url', $return_url);
        $this->var()->find('phase', $phase, 'pre:trim:str:1', 'form');

        $modInfo = $this->mod()->getInfo($id);
        if (!isset($modInfo)) {
            return;
        }

        $modName     = $modInfo['name'];

        // Security
        if (!$this->sec()->check('AdminModules', 0, 'All', "$modName::$id")) {
            return;
        }

        $object = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => $modName]);
        $filesettings = $this->mod()->getFileInfo($modName);
        if (empty($filesettings)) {
            return;
        }

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
                        $return_url = $this->ctl()->getModuleURL('modules', 'admin', 'modifyproperties', ['id' => $id]);
                    }
                    $this->ctl()->redirect($return_url);
                    return true;
                }
            }
        }

        $displayName = $modInfo['displayname'];
        $data['admincapable'] = $modInfo['admincapable'];
        $data['usercapable'] = $modInfo['usercapable'];
        $data['adminallowed'] = $filesettings['admin'];
        $data['userallowed'] = $filesettings['user'];
        $data['savechangeslabel'] = $this->ml('Save Changes');
        $data['object'] = $object;
        $data['authid'] = $this->sec()->genAuthKey('modules');
        $data['id'] = $id;
        $data['displayname'] = $modInfo['displayname'];
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }
        return $data;
    }
}
