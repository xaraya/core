<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use xarController;
use xarPrivilege;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin addprivilege function
 * @extends MethodClass<AdminGui>
 */
class AddprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addPrivilege - add a privilege to the repository
     * This is an action page
     * @see AdminGui::addprivilege()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AddPrivileges')) {
            return;
        }

        xarVar::fetch('pname', 'isset', $pname, null, xarVar::DONT_SET);
        xarVar::fetch('prealm', 'isset', $prealm, 'All', xarVar::NOT_REQUIRED);
        xarVar::fetch('pmodule', 'isset', $pmodule, 'All', xarVar::DONT_SET);
        xarVar::fetch('pcomponent', 'isset', $pcomponent, null, xarVar::DONT_SET);
        xarVar::fetch('ptype', 'isset', $type, null, xarVar::DONT_SET);
        xarVar::fetch('plevel', 'isset', $plevel, null, xarVar::DONT_SET);
        xarVar::fetch('pparentid', 'isset', $pparentid, null, xarVar::DONT_SET);
        xarVar::fetch('pinstance', 'array', $pinstances, [], xarVar::NOT_REQUIRED);

        $instance = "";
        foreach ($pinstances as $pinstance) {
            $instance .= $pinstance . ":";
        }
        if ($instance == "") {
            $instance = "All";
        } else {
            $instance = substr($instance, 0, strlen($instance) - 1);
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        if ($type == "empty") {

            // this is just a container for other privileges
            $pargs = ['name' => $pname,
                'realm' => 'All',
                'module' => 'empty',
                'component' => 'All',
                'instance' => 'All',
                'level' => 0,
                'parentid' => 'All',
            ];
        } else {

            // this is privilege has its own rights assigned
            $pargs = ['name'   => $pname,
                'realm'     => $prealm, // now has realm id in it!!!
                'module'    => $pmodule,
                'component' => $pcomponent,
                'instance'  => $instance,
                'level'     => $plevel,
                'parentid'  => $pparentid,
            ];
        }

        //Call the Privileges class
        sys::import('modules.privileges.class.privilege');
        $priv = new xarPrivilege($pargs);

        //Try to add the privilege and bail if an error was thrown
        if (!$priv->add()) {
            return;
        }

        xarSession::setVar('privileges_statusmsg', xarML(
            'Privilege Added',
            'privileges'
        ));

        // redirect to the next page
        xarController::redirect(xarController::URL('privileges', 'admin', 'new'), null, $this->getContext());
        return true;
    }
}
