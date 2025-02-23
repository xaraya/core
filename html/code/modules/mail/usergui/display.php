<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\UserGui;
use xarController;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
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
     * @see UserGui::display()
     */
    public function __invoke(array $args = [])
    {
        $this->var()->find('itemid', $itemid, 'int:1:', 0);
        if (empty($itemid)) {
            return xarController::notFound(null, $this->getContext());
        }
        xarController::redirect(xarController::URL('mail', 'admin', 'view', ['itemid' => $itemid]), null, $this->getContext());
        return true;
    }
}
