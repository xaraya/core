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
use xarMLS;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin sendtest function
 * @extends MethodClass<AdminGui>
 */
class SendtestMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Test the email settings
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return bool|void true on success or void on failure
     * @see AdminGui::sendtest()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('ManageMail')) {
            return;
        }

        // Get parameters from whatever input we need
        if (!xarVar::fetch('message', 'str:1:', $message)) {
            return;
        }
        if (!xarVar::fetch('subject', 'str:1', $subject)) {
            return;
        }
        if (!xarVar::fetch('email', 'email', $email, '')) {
            return;
        }
        if (!xarVar::fetch('name', 'str:1', $name, '')) {
            return;
        }
        if (!xarVar::fetch('emailcc', 'email', $emailcc, '')) {
            return;
        }
        if (!xarVar::fetch('namecc', 'str:1', $namecc, '')) {
            return;
        }
        if (!xarVar::fetch('emailbcc', 'email', $emailbcc, '')) {
            return;
        }
        if (!xarVar::fetch('namebcc', 'str:1', $namebcc, '')) {
            return;
        }

        // Confirm authorisation code.
        if (!xarSec::confirmAuthKey()) {
            //return xarController::badRequest('bad_author', $this->getContext());
        }
        if (empty($email)) {
            $email = xarModVars::get('mail', 'adminmail');
        }
        if (empty($name)) {
            $name = xarModVars::get('mail', 'adminname');
        }

        if (!xarVar::fetch('when', 'str:1', $when, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($when)) {
            $when .= ' GMT';
            $when = strtotime($when);
            $when -= xarMLS::userOffset() * 3600;
        } else {
            $when = 0;
        }

        $htmlmessage = $message;

        if (!$adminapi->sendmail(['info' => $email,
            'name' => $name,
            'ccinfo' => $emailcc,
            'ccname' => $namecc,
            'bccinfo' => $emailbcc,
            'bccname' => $namebcc,
            'subject' => $subject,
            'message' => $message,
            'htmlmessage' => $htmlmessage,
            'when' => $when])) {
            return;
        }

        // lets update status and display updated configuration
        xarController::redirect(xarController::URL('mail', 'admin', 'compose', ['confirm' => 1]), null, $this->getContext());
        return true;
    }
}
