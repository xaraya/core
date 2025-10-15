<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use Exception;
use VariableValidationException;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getuserhome function
 * @extends MethodClass<UserApi>
 */
class GetuserhomeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['itemid']
     * @return string|void representing the user home
     * @see UserApi::getuserhome()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!empty($itemid) && !is_numeric($itemid)) {
            throw new VariableValidationException(['itemid',$itemid,'numeric']);
        }

        // the last resort admin always goes to the base main page
        $lastresortvalue = $this->mod('privileges')->getVar('lastresort');
        $userhome = !empty($lastresort) ? '[base]' : $this->mod()->getUserVar('userhome', $itemid);

        // otherwise look for the role's userhome
        if (empty($userhome) || ($userhome == 'undefined')) {
            $notdone = true;
            $userhome = "";
            try {
                $settings = explode(',', $this->mod()->getVar('duvsettings'));
                if (in_array('primaryparent', $settings)) {
                    // go for the primary parent's userhome
                    $parentid = $this->mod()->getUserVar('primaryparent', $itemid);
                    if (!empty($parentid)) {
                        return $userapi->getuserhome(['itemid' => $parentid]);
                    }
                }
            } catch (Exception $e) {
            }
            if ($notdone) {
                // take the first userhome url encountered.
                // TODO: what would be a more logical choice?
                $role = xarRoles::get($itemid);
                foreach ($role->getParents() as $parent) {
                    return $userapi->getuserhome(['itemid' => $parent->getID()]);
                }
            }
        }
        return $userhome;
    }
}
