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
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem user password function
 * @extends MethodClass<UserGui>
 */
class PasswordMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Display a password request page.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args Arguments passed to Gui function.
     * @return array Data for the display template
     * @see UserGui::password()
     */
    public function __invoke(array $args = [])
    {
        return xarMod::guiFunc('roles', 'user', 'lostpassword', $args, $this->getContext());
    }
}
