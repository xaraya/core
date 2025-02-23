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
use Xaraya\Modules\Mail\AdminApi;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin createq function
 * @extends MethodClass<AdminGui>
 */
class CreateqMethod extends MethodClass
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
     * @see AdminGui::createq()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // What do we need to do
        xarVar::fetch('name', 'str:1:12', $qName);

        // Do we have the master ?
        if (!$qdefInfo = $adminapi->getqdef()) {
            // Redirect to the view page, which offers to create one
            xarController::redirect(xarController::URL('mail', 'admin', 'view'), null, $this->getContext());
            return true;
        }

        // Seems ok, call the create function
        $qData = $adminapi->createq(['name' => $qName]);
        if (!$qData) {
            return;
        } // exception

        // Show the status screen again,
        xarController::redirect(xarController::URL('mail', 'admin', 'qstatus'), null, $this->getContext());
        return true;
    }
}
