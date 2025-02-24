<?php

/**
 * Handle module installer functions
 *
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail;

use Xaraya\Modules\InstallerClass;
use xarMasks;
use xarModHooks;
use xarModVars;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced mail_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the mail module
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return bool true on success or false on failure
     */
    public function init()
    {
        $this->mod()->setVar('server', 'mail');
        $this->mod()->setVar('replyto', '0');
        $this->mod()->setVar('wordwrap', '78');
        $this->mod()->setVar('priority', '3');
        $this->mod()->setVar('smtpPort', '25');
        $this->mod()->setVar('smtpHost', 'Your SMTP Host');
        $this->mod()->setVar('encoding', '8bit');
        $this->mod()->setVar('smtpAuth', '');
        $this->mod()->setVar('smtpSecure', '');
        $this->mod()->setVar('smtpUserName', '');
        $this->mod()->setVar('smtpPassword', '');
        $this->mod()->setVar('html', false);
        $this->mod()->setVar('searchstrings', serialize('%%Search%%'));
        $this->mod()->setVar('replacestrings', serialize('Replace %%Search%% with this text'));
        $this->mod()->setVar('use_external_lib', false);
        $this->mod()->setVar('embed_images', false);
        $this->mod()->setVar('debug', false);

        xarModHooks::register('item', 'create', 'API', 'mail', 'admin', 'hookmailcreate');
        xarModHooks::register('item', 'delete', 'API', 'mail', 'admin', 'hookmaildelete');
        xarModHooks::register('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');

        xarMasks::register('ViewMail', 'All', 'mail', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditMail', 'All', 'mail', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddMail', 'All', 'mail', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageMail', 'All', 'mail', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminMail', 'All', 'mail', 'All', 'All', 'ACCESS_ADMIN');

        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Activate the mail module
     * @access public
     * @return bool
     */
    public function activate()
    {
        return true;
    }

    /**
     * Upgrade this module from an old version
     * @param string oldversion
     * @return bool true on success, false on failure
     * @todo create separate xar_mail_queue someday
     * @todo allow mail gateway functionality
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            default:
                break;
        }
        return true;
    }

    /**
     * Delete this module
     * @return bool
     */
    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
