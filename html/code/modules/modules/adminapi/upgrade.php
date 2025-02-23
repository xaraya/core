<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use EmptyParameterException;
use Exception;
use xarDB;
use xarMod;
use xarSession;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi upgrade function
 * @extends MethodClass<AdminApi>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a module
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] registered module id
     * @return bool|void true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::upgrade()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Get module information
        $modInfo = $this->mod()->getInfo($regid);
        if (empty($modInfo)) {
            $this->session()->setVar('errormsg', $this->ml('No such module'));
            return false;
        }

        // Module deletion function
        if (!$adminapi->executeinitfunction(['regid'    => $regid,
            'function' => 'upgrade'])) {
            //Raise an Exception
            return;
        }

        // Update state of module
        $res = $adminapi->setstate(['regid' => $regid,
            'state' => xarMod::STATE_INACTIVE]);
        if (!isset($res)) {
            return;
        }

        // Get the new version information...
        $modFileInfo = $this->mod()->getFileInfo($modInfo['osdirectory']);
        if (!isset($modFileInfo)) {
            return;
        }

        // Bug 1671 - Invalid SQL
        // If the module fields returned from $this->mod()->getFileInfo()
        // are set to false, then they must be set to a some valid value
        // or a SQL error will occur due to null and zero length fields.
        if (!$modFileInfo['admin_capable']) {
            $modFileInfo['admin_capable'] = 0;
        }
        if (!$modFileInfo['user_capable']) {
            $modFileInfo['user_capable'] = 0;
        }
        if (!$modFileInfo['class']) {
            $modFileInfo['class'] = 'Miscellaneous';
        }
        if (!$modFileInfo['category']) {
            $modFileInfo['category'] = 'Miscellaneous';
        }

        // Note the changes in the database...
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $sql = "UPDATE $xartable[modules]
                SET version = ?, admin_capable = ?, user_capable = ?,
                    class = ?, category = ?
                WHERE regid = ?";
        $bindvars = [$modFileInfo['version'], $modFileInfo['admin_capable'],
            $modFileInfo['user_capable'],$modFileInfo['class'],
            $modFileInfo['category'], $regid];
        $dbconn->Execute($sql, $bindvars);

        // Message to display in the module list view (only for core modules atm)
        if (!$this->session()->getVar('statusmsg')) {
            if (substr($modFileInfo['class'], 0, 4)  == 'Core') {
                $this->session()->setVar('statusmsg', $modInfo['name']);
            }
        } else {
            if (substr($modFileInfo['class'], 0, 4)  == 'Core') {
                $this->session()->setVar('statusmsg', $this->session()->getVar('statusmsg') . ', ' . $modInfo['name']);
            }
        }
        // Success
        return true;
    }
}
