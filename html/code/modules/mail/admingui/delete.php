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
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin delete function
 * @extends MethodClass<AdminGui>
 */
class DeleteMethod extends MethodClass
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
     * @see AdminGui::delete()
     */
    public function __invoke(array $args = [])
    {
        // Are we legitimally here?
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }
        // Security
        if (!xarSecurity::check('ManageMail')) {
            return;
        }

        // Required parameters
        $this->var()->find('itemid', $itemid, 'int:1:', 0);
        $this->var()->find('objectid', $objectid, 'int:1:', 0);
        if (empty($itemid) || empty($objectid)) {
            return xarController::notFound(null, $this->getContext());
        }

        $qdefObject = $this->data()->getObject(['objectid' => $objectid]);
        if (!$qdefObject) {
            return;
        }

        $result = $qdefObject->deleteItem(['itemid' => $itemid]);
        if (!$result) {
            return;
        }

        return xarController::redirect(xarController::URL('mail', 'admin', 'view'), null, $this->getContext());
    }
}
