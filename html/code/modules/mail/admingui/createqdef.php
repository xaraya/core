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
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

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
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        // Are we legitimately here
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // First determine whether we need to look at the name entered, or the object chosen
        if (!xarVar::fetch('qdef_choose', 'int:1', $qdef_choose, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        switch ($qdef_choose) {
            case 1:  // Name entered
                $qdefNew = true;
                if (!xarVar::fetch('qdef_name_enter', 'str:1:12', $qdefName)) {
                    return;
                }
                break;
            case 2:  // Object chosen
                $qdefNew = false;
                if (!xarVar::fetch('qdef_name_choose', 'int:1:', $qdefObjectId)) {
                    return;
                }
                if (empty($qdefObjectId)) {
                    return xarController::notFound(null, $this->getContext());
                }
                // Get the name of the object from dd
                $qdefObject = xarMod::apiFunc('dynamicdata', 'user', 'getobject', ['objectid' => $qdefObjectId]);
                if (!isset($qdefObject)) {
                    return;
                }
                $qdefName = $qdefObject->name;
                break;
            default:
                return xarController::notFound(null, $this->getContext());
        }

        if ($qdefNew) {
            $xmlDef = @file_get_contents(sys::code() . 'modules/mail/xardata/qdef.xml'); // if it fails, sane check will catch it.
            // Take the xml and the objectname and try to create the object
            $qdefObjectId = xarMod::apiFunc('dynamicdata', 'util', 'import', ['objectname' => $qdefName, 'xml' => $xmlDef]);
            if (!isset($qdefObjectId)) {
                return;
            }

            // The file contained itemtype -1 which needs to be corrected now.
            // We created the object successfully, register it as soon as possible (getitemtypes depends on it, for one)
            xarModVars::set('mail', 'queue-definition', $qdefName);
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
            xarModVars::set('mail', 'queue-definition', $qdefName);
        }
        xarController::redirect(xarController::URL('mail', 'admin', 'view'), null, $this->getContext());
        return true;
    }
}
