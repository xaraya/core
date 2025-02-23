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
        if (!$this->sec()->checkAccess('ManageMail')) {
            return;
        }

        // Get parameters from whatever input we need
        $this->var()->find('message', $message, 'str:1:');
        $this->var()->find('subject', $subject, 'str:1:');
        $this->var()->find('email', $email, 'email', '');
        $this->var()->find('name', $name, 'str:1:', '');
        $this->var()->find('emailcc', $emailcc, 'email', '');
        $this->var()->find('namecc', $namecc, 'str:1:', '');
        $this->var()->find('emailbcc', $emailbcc, 'email', '');
        $this->var()->find('namebcc', $namebcc, 'str:1:', '');

        // Confirm authorisation code.
        if (!$this->sec()->confirmAuthKey()) {
            //return $this->ctl()->badRequest('bad_author');
        }
        if (empty($email)) {
            $email = xarModVars::get('mail', 'adminmail');
        }
        if (empty($name)) {
            $name = xarModVars::get('mail', 'adminname');
        }

        $this->var()->find('when', $when, 'str:1', '');
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
        $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'compose', ['confirm' => 1]));
        return true;
    }
}
