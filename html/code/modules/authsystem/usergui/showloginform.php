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
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem user showloginform function
 * @extends MethodClass<UserGui>
 */
class ShowloginformMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Shows the user login form when login block is not active
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @author Jo Dalle Nogare <jojodeexaraya.com>
     * @param array<string,mixed> $args Optional 'redirecturl' parameter
     * @return array|bool Returns data for display template.
     * @see UserGui::showloginform()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        $this->var()->find('redirecturl', $redirecturl, 'str:1:254', '');
        if (empty($redirecturl)) {
            $redirecturl = $this->mod()->getVar('forwarding_page');
            if (empty($redirecturl)) {
                $redirecturl = $this->ctl()->getBaseURL();
            }
        }
        $redirecturl = $this->var()->prepHTML($redirecturl);
        $truecurrenturl = $this->ctl()->getCurrentURL([], false);
        $urldata = $this->mod()->apiFunc('roles', 'user', 'parseuserhome', ['url' => $redirecturl,'truecurrenturl' => $truecurrenturl]);
        $data['redirecturl'] = $urldata['redirecturl'];

        // If we don't ask to forward, then forward immediately
        if (!(int) $this->mod()->getVar('ask_forward') && $this->user()->isLoggedIn()) {
            $this->ctl()->redirect($data['redirecturl']);
            return true;
        }

        return $data;
    }
}
