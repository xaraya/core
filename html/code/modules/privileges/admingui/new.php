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
use xarMod;
use xarPrivileges;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;
use SecurityLevel;

sys::import('xaraya.modules.method');

/**
 * privileges admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * new - create a new privilege
     * Takes no parameters
     * @return array|void data for the template display
     * @see AdminGui::new()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AddPrivileges')) {
            return;
        }

        $data = [];

        xarVar::fetch('id', 'isset', $data['id'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('pname', 'isset', $data['pname'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('pparentid', 'isset', $data['pparentid'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('prealm', 'isset', $data['prealm'], 'All', xarVar::NOT_REQUIRED);
        xarVar::fetch('pmodule', 'isset', $data['pmodule'], 'All', xarVar::NOT_REQUIRED);
        xarVar::fetch('pcomponent', 'isset', $data['pcomponent'], 'All', xarVar::NOT_REQUIRED);
        xarVar::fetch('pinstance', 'isset', $data['pinstance'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('plevel', 'isset', $data['plevel'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('ptype', 'isset', $data['ptype'], '', xarVar::NOT_REQUIRED);
        xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED);
        xarVar::fetch('trees', 'isset', $trees, null, xarVar::NOT_REQUIRED);

        // Clear Session Vars
        xarSession::delVar('privileges_statusmsg');

        // remove duplicate entries from the list of privileges
        $privileges = [];
        $names = [];
        $privileges[] = ['id' => 0,
            'name' => ''];
        foreach (xarPrivileges::getprivileges() as $temp) {
            $nam = $temp['name'];
            if (!in_array($nam, $names)) {
                $names[] = $nam;
                $privileges[] = $temp;
            }
        }
        //Load Template
        $instances = $adminapi->getinstances(['module' => $data['pmodule'],'component' => $data['pcomponent']]);
        // send to external wizard if necessary
        if (!empty($instances['external']) && $instances['external'] == "yes") {
            $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname=' . $data['pname'] . '&amp;extrealm=' . $data['prealm'] . '&amp;extmodule=' . $data['pmodule'] . '&amp;extcomponent=' . $data['pcomponent'] . '&amp;extlevel=' . $data['plevel'];
            $data['instances'] = [];
        } else {
            $data['instances'] = $instances;
        }

        $accesslevels = SecurityLevel::$displayMap;
        unset($accesslevels[-1]);
        $data['levels'] = [];
        foreach ($accesslevels as $key => $value) {
            $data['levels'][] = ['id' => $key, 'name' => $value];
        }

        $data['authid'] = xarSec::genAuthKey();
        $data['realms'] = xarPrivileges::getrealms();
        $data['privileges'] = $privileges;
        $data['components'] = $adminapi->getcomponents(['modid' => xarMod::getRegID($data['pmodule'])]);
        return $data;
    }
}
