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
use ForbiddenOperationException;
use xarUser;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem user logout function
 * @extends MethodClass<UserGui>
 */
class LogoutMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Log a user out of the system.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool|void Returns true if the user has been logged out successfullly.
     * @throws \ForbiddenOperationException Thrown if the user could not be logged out.
     * @see UserGui::logout()
     */
    public function __invoke(array $args = [])
    {
        $redirect = $this->ctl()->getBaseURL();

        // Get input parameters
        $this->var()->find('redirecturl', $redirecturl, 'str:1:254', $redirect);

        $defaultauthdata = $this->mod()->apiFunc('roles', 'user', 'getdefaultauthdata');
        $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];
        $authmodule = $defaultauthdata['defaultauthmodname'];
        // Defaults
        //if (preg_match('/$authmodule}/',$redirecturl)) {
        if (strstr($redirecturl, $defaultlogoutmodname)) {
            $redirecturl = $redirect;
        }

        // Log user out
        if (!$this->user()->logOut()) {
            throw new ForbiddenOperationException(['authsystem', 'logout'], $this->ml('Problem Logging Out.  Module #(1) Function #(2)'), $this->getContext());
        }
        $this->ctl()->redirect($redirecturl);
        return true;
    }
}
