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
    /**
     * Configure this module - override this method
     *
     * @todo use this instead of init() etc. for standard installation
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'mail_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the mail module
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return bool true on success or false on failure
     */
    public function init()
    {
        xarModVars::set('mail', 'server', 'mail');
        xarModVars::set('mail', 'replyto', '0');
        xarModVars::set('mail', 'wordwrap', '78');
        xarModVars::set('mail', 'priority', '3');
        xarModVars::set('mail', 'smtpPort', '25');
        xarModVars::set('mail', 'smtpHost', 'Your SMTP Host');
        xarModVars::set('mail', 'encoding', '8bit');
        xarModVars::set('mail', 'smtpAuth', '');
        xarModVars::set('mail', 'smtpSecure', '');
        xarModVars::set('mail', 'smtpUserName', '');
        xarModVars::set('mail', 'smtpPassword', '');
        xarModVars::set('mail', 'html', false);
        xarModVars::set('mail', 'searchstrings', serialize('%%Search%%'));
        xarModVars::set('mail', 'replacestrings', serialize('Replace %%Search%% with this text'));
        xarModVars::set('mail', 'use_external_lib', false);
        xarModVars::set('mail', 'embed_images', false);
        xarModVars::set('mail', 'debug', false);

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
