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
use xarController;
use xarMod;
use xarModVars;
use xarServer;
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
        $redirect = xarModVars::get('authsystem', 'frontend_page');
        if (!empty($redirect)) {
            $truecurrenturl = xarServer::getCurrentURL([], false);
            $urldata = xarMod::apiFunc('roles', 'user', 'parseuserhome', ['url' => $redirect,'truecurrenturl' => $truecurrenturl]);
            xarController::redirect($urldata['redirecturl'], null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL('authsystem', 'user', 'showloginform'), null, $this->getContext());
        }
        return true;
    }
}
