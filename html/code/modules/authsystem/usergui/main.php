<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\UserGui;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main user interface function of this module.
     * This function is the default function, and is called whenever the module is
     * initiated without defining arguments.
     * The function redirects to the showloginform funtion.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @return bool True after redirection
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        $redirect = $this->mod()->getVar('frontend_page');
        if (!empty($redirect)) {
            $truecurrenturl = $this->ctl()->getCurrentURL([], false);
            $urldata = $this->mod()->apiFunc('roles', 'user', 'parseuserhome', ['url' => $redirect,'truecurrenturl' => $truecurrenturl]);
            $this->ctl()->redirect($urldata['redirecturl']);
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL('authsystem', 'user', 'showloginform'));
        }
        return true;
    }
}
