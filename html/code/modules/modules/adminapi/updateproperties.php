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
use xarDB;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi updateproperties function
 * @extends MethodClass<AdminApi>
 */
class UpdatepropertiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update module information
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id number of the module to update<br/>
     * string   $args['displayname'] the new display name of the module<br/>
     * string   $args['admincapable'] the whether the module shows an admin menu<br/>
     * string   $args['usercapable'] the whether the module shows a user menu
     * @return bool|void true on success, false on failure
     * @see AdminApi::updateproperties()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Security Check
        if (!xarSecurity::check('AdminModules', 0, 'All', "All:All:$regid")) {
            return;
        }

        // Update
        $xartable = $this->db()->getTables();
        $q = 'UPDATE ' . $xartable['modules'] . ' SET ';
        $uparts = [];
        $bindvars = [];
        //    if (isset($displayname)) {$uparts[] = 'directory=?'; $bindvars[] = $displayname;}
        if (isset($admincapable)) {
            $uparts[] = 'admin_capable=?';
            $bindvars[] = $admincapable;
        }
        if (isset($usercapable)) {
            $uparts[] = 'user_capable=?';
            $bindvars[] = $usercapable;
        }
        if (isset($version)) {
            $uparts[] = 'version=?';
            $bindvars[] = $version;
        }
        if (isset($class)) {
            $uparts[] = 'class=?';
            $bindvars[] = $class;
        }
        if (isset($category)) {
            $uparts[] = 'category=?';
            $bindvars[] = $category;
        }
        if (!empty($uparts)) {
            // We have something to update
            $q .= join(',', $uparts) . ' WHERE regid=?';
            $bindvars[] = $regid;
            $dbconn = $this->db()->getConn();
            $dbconn->Execute($q, $bindvars);
        }
        return true;
    }
}
