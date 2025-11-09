<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use xarSystemVars;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin finish function
 * @extends MethodClass<AdminGui>
 */
class FinishMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installer
     * @package modules\installer\installer
     * @subpackage installer
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/200.html
     * @see AdminGui::finish()
     */
    public function __invoke(array $args = [])
    {
        $this->var()->find('returnurl', $returnurl, 'str', 'site');

        // Default debug admin @fixme this was just configured by the user, and could be anything...
        $admin = $this->mod()->apiFunc('roles', 'user', 'get', ['uname' => 'admin']);
        if (!empty($admin) && !empty($admin['id'])) {
            $this->config()->setVar('Site.User.DebugAdmins', [$admin['id']]);
        }

        // Default for the site time zone is the system time zone
        $this->config()->setVar('Site.Core.TimeZone', $this->sysConfig()->getVar('SystemTimeZone'));

        // Defaults for templating engine options
        $this->config()->setVar('Site.BL.CompressWhitespace', 1);
        $this->config()->setVar('Site.BL.MemCacheTemplates', false);

        // Default for AJAX calls
        $this->config()->setVar('Site.Core.AllowAJAX', true);

        // Display variable values in exceptions?
        $this->config()->setVar('Site.BL.ExceptionDisplay', false);

        // Declare the installation a success
        $variables = ['DB.Installation' => 3];
        $this->mod()->apiFunc('installer', 'admin', 'modifysystemvars', ['variables' => $variables]);

        switch ($returnurl) {
            case ('base'):
                $this->ctl()->redirect($this->ctl()->getModuleURL('base', 'admin', 'modifyconfig'));
                // no break
            case ('modules'):
                $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'list'));
                // no break
            case ('blocks'):
                $this->ctl()->redirect($this->ctl()->getModuleURL('blocks', 'admin', 'view_instances'));
                // no break
            case ('site'):
            default:
                $this->ctl()->redirect('index.php');
        }
        return true;
    }
}
