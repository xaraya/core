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
use Xaraya\Modules\Privileges\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin removebranch function
 * @extends MethodClass<AdminGui>
 */
class RemovebranchMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removebranch - remove a privilege from a privilege
     * Remove a privilege as a member of another privilege.
     * This is an action page..
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return array|string|void
     * @see AdminGui::removebranch()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditPrivileges')) {
            return;
        }

        // get input from any view of this page
        $this->var()->find('childid', $childid, 'int', null);
        $this->var()->find('parentid', $parentid, 'int', null);
        if (empty($childid)) {
            return $this->ctl()->notFound();
        }
        if (empty($parentid)) {
            return $this->ctl()->notFound();
        }

        // call the API function
        if (!$adminapi->removemember(['parentid' => $parentid, 'childid' => $childid])) {
        }

        // redirect to the next page
        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'privileges',
            'admin',
            'viewprivileges'
        ));
        return true;
    }
}
