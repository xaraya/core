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
use xarModHooks;
use xarPrivileges;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin updateprivilege function
 * @extends MethodClass<AdminGui>
 */
class UpdateprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * updateprivilege - update a privilege
     * @see AdminGui::updateprivilege()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditPrivileges')) {
            return;
        }

        // Clear Session Vars
        xarSession::delVar('privileges_statusmsg');

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $this->var()->check('id', $id);
        $this->var()->check('pname', $name);
        $this->var()->find('prealm', $realm, 'isset', 'All');
        $this->var()->find('pmodule', $pmodule, 'isset', 'All');
        $this->var()->check('pcomponent', $component, 'isset', 'All');
        $this->var()->check('ptype', $type);
        $this->var()->check('plevel', $level);
        $this->var()->find('pinstance', $pinstance);

        $instance = "";
        if (!empty($pinstance)) {
            if (is_array($pinstance)) {
                $instance = implode(':', $pinstance);
            } else {
                // for wizard-based privileges
                $instance = $pinstance;
            }
        }
        if ($instance == "") {
            $instance = "All";
        }

        // Security Check
        if (!xarSecurity::check('EditPrivileges', 0, 'Privileges', $name)) {
            return;
        }

        // call the Privileges class and update the values

        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($id);
        if ($type == "empty") {

            // this is just a container for other privileges
            $priv->setName($name);
            $priv->setRealm('All');
            $priv->setModuleID(null);
            $priv->setComponent('All');
            $priv->setInstance('All');
            $priv->setLevel(0);
        } else {
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModuleID($pmodule);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
        }

        //Try to update the privilege to the repository and bail if an error was thrown
        if (!$priv->update()) {
            return;
        }

        xarModHooks::call('item', 'update', $id, '');

        xarSession::setVar('privileges_statusmsg', xarML(
            'Privilege Modified',
            'privileges'
        ));

        // redirect to the next page
        xarController::redirect(xarController::URL(
            'privileges',
            'admin',
            'modifyprivilege',
            ['id' => $id]
        ), null, $this->getContext());
        return true;
    }
}
