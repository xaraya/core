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
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin modify function
 * @extends MethodClass<AdminGui>
 */
class ModifyMethod extends MethodClass
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
     * @see AdminGui::modify()
     */
    public function __invoke(array $args = [])
    {
        if (!xarVar::fetch('itemid', 'int:1:', $itemid, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($itemid)) {
            return xarController::notFound(null, $this->getContext());
        }
        xarController::redirect(xarController::URL('mail', 'admin', 'view', ['itemid' => $itemid]), null, $this->getContext());
        return true;
    }
}
