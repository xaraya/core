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
use xarPrivilege;
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
        if (!$this->sec()->checkAccess('AddPrivileges')) {
            return;
        }

        $this->var()->check('pname', $pname);
        $this->var()->find('prealm', $prealm, 'isset', 'All');
        $this->var()->check('pmodule', $pmodule, 'isset', 'All');
        $this->var()->check('pcomponent', $pcomponent);
        $this->var()->check('ptype', $type);
        $this->var()->check('plevel', $plevel);
        $this->var()->check('pparentid', $pparentid);
        $this->var()->find('pinstance', $pinstances, 'array', []);

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
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
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

        $this->session()->setVar('privileges_statusmsg', $this->ml(
            'Privilege Added',
            'privileges'
        ));

        // redirect to the next page
        $this->ctl()->redirect($this->ctl()->getModuleURL('privileges', 'admin', 'new'));
        return true;
    }
}
