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
use Query;
use xarDB;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi countitems function
 * @extends MethodClass<AdminApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\modules
     * @subpackage modules
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/1.html
     * @see AdminApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Set some defaults
        if (!isset($state)) {
            $state = xarMod::STATE_ACTIVE;
        }
        if (!isset($include_core)) {
            $include_core = true;
        }

        // Determine the tables we are going to use
        $tables = $this->db()->getTables();
        $q = new Query('SELECT', $tables['modules']);

        if (!empty($regid)) {
            $q->eq('regid', $regid);
        }

        if (!empty($name)) {
            if (is_array($name)) {
                $q->in('name', $name);
            } else {
                $q->eq('name', $name);
            }
        }

        if (!empty($systemid)) {
            $q->eq('id', $systemid);
        }

        if ($state != xarMod::STATE_ANY) {
            if ($state != xarMod::STATE_INSTALLED) {
                $q->eq('state', $state);
            } else {
                $q->ne('state', xarMod::STATE_UNINITIALISED);
                $q->lt('state', xarMod::STATE_MISSING_FROM_INACTIVE);
                $q->ne('state', xarMod::STATE_MISSING_FROM_UNINITIALISED);
            }
        }

        if (!empty($modclass)) {
            $q->eq('class', $modclass);
        }
        if (!empty($category)) {
            $q->eq('category', $category);
        }

        if (!$include_core) {
            $coremods = ['base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories'];
            $q->notin('name', $coremods);
        }

        if (!empty($user_capable)) {
            $q->eq('user_capable', (int) $user_capable);
        }
        if (!empty($admin_capable)) {
            $q->eq('admin_capable', (int) $admin_capable);
        }

        $q->run();
        $result = $q->output();
        return count($result);
    }
}
