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
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // What do we need to do
        $this->var()->find('name', $qName, 'str:1:12');

        // Do we have the master ?
        if (!$qdefInfo = $adminapi->getqdef()) {
            // Redirect to the view page, which offers to create one
            $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'view'));
            return true;
        }

        // Seems ok, call the create function
        $qData = $adminapi->createq(['name' => $qName]);
        if (!$qData) {
            return;
        } // exception

        // Show the status screen again,
        $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'qstatus'));
        return true;
    }
}
