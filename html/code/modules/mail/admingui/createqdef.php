<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminGui;
use Xaraya\Modules\Mail\UserApi;
use DataObjectFactory;
use sys;

/**
 * mail admin createqdef function
 * @extends MethodClass<AdminGui>
 */
class CreateqdefMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\mail
     * @subpackage mail
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/771.html
     * @see AdminGui::createqdef()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        // Are we legitimately here
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // First determine whether we need to look at the name entered, or the object chosen
        $this->var()->find('qdef_choose', $qdef_choose, 'int:1', 0);
        switch ($qdef_choose) {
            case 1:  // Name entered
                $qdefNew = true;
                $this->var()->find('qdef_name_enter', $qdefName, 'str:1:12');
                break;
            case 2:  // Object chosen
                $qdefNew = false;
                $this->var()->find('qdef_name_choose', $qdefObjectId, 'int:1:');
                if (empty($qdefObjectId)) {
                    return $this->ctl()->notFound();
                }
                // Get the name of the object from dd
                $qdefObject = $this->data()->getObject(['objectid' => $qdefObjectId]);
                if (!isset($qdefObject)) {
                    return;
                }
                $qdefName = $qdefObject->name;
                break;
            default:
                return $this->ctl()->notFound();
        }

        if ($qdefNew) {
            $xmlDef = @file_get_contents(sys::code() . 'modules/mail/xardata/qdef.xml'); // if it fails, sane check will catch it.
            // Take the xml and the objectname and try to create the object
            $qdefObjectId = $this->mod()->apiFunc('dynamicdata', 'util', 'import', ['objectname' => $qdefName, 'xml' => $xmlDef]);
            if (!isset($qdefObjectId)) {
                return;
            }

            // The file contained itemtype -1 which needs to be corrected now.
            // We created the object successfully, register it as soon as possible (getitemtypes depends on it, for one)
            $this->mod()->setVar('queue-definition', $qdefName);
            // Get the itemtypes of the mail module
            $itemtypes = $userapi->getitemtypes();
            // Get the max value from the keys and add one
            ksort($itemtypes);
            end($itemtypes);
            $newItemtype = key($itemtypes) + 1;
            if ($newItemtype == 0) {
                $newItemtype++;
            } // prevent the 0 value

            $params = ['objectid' => $qdefObjectId, 'itemtype' => $newItemtype];
            $itemid = DataObjectFactory::updateObject($params);

        } else {
            // All went well, we can set the modvar now
            $this->mod()->setVar('queue-definition', $qdefName);
        }
        $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'view'));
        return true;
    }
}
