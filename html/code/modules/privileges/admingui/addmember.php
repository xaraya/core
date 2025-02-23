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
use DuplicateException;
use xarController;
use xarPrivileges;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin addmember function
 * @extends MethodClass<AdminGui>
 */
class AddmemberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addMember - assign a privilege as a member of another privilege
     * Make a privilege a member of another privilege.
     * This is an action page..
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return mixed
     * @see AdminGui::addmember()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AddPrivileges')) {
            return;
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        xarVar::fetch('ppid', 'isset', $id, null, xarVar::DONT_SET);
        xarVar::fetch('privid', 'isset', $privid, null, xarVar::DONT_SET);

        if (empty($id) || empty($privid)) {
            xarController::redirect(xarController::URL(
                'privileges',
                'admin',
                'modifyprivilege',
                ['id' => $id]
            ), null, $this->getContext());
            return true;
        }

        // call the Privileges class and get the parent and child objects
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($id);
        $member = xarPrivileges::getPrivilege($privid);

        // we bail if there is a loop: the child is already an ancestor of the parent
        $found = false;
        $descendants = $member->getDescendants();
        foreach ($descendants as $descendant) {
            if ($descendant->getID() == $priv->getID()) {
                $found = true;
            }
        }
        if ($found) {
            throw new DuplicateException(null, 'The privilege you are trying to assign to is already a component of the one you are assigning.');
        }

        // assign the child to the parent and bail if an error was thrown
        // we bail if the child is already a member of the *parent*
        // if the child was a member of an ancestor further up that would be OK.
        $found = false;
        $children = $priv->getChildren();
        foreach ($children as $child) {
            if ($child->getID() == $member->getID()) {
                $found = true;
            }
        }
        if (!$found) {
            if (!$priv->addMember($member)) {
                return;
            }
        }

        // set the session variable
        xarSession::setVar('privileges_statusmsg', xarML(
            'Added to Privilege',
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
