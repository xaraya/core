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
use xarRoles;
use VariableValidationException;

/**
 * roles userapi getprimaryparent function
 * @extends MethodClass<UserApi>
 */
class GetprimaryparentMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @param int $itemid whether
     * @return int id representing the role's primary parent group
     * @see UserApi::getprimaryparent()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($itemid) && !is_numeric($itemid)) {
            throw new VariableValidationException(['itemid',$itemid,'numeric']);
        }

        $parentid = $this->mod()->getUserVar('primaryparent', $itemid);
        $role = $this->user()->getRole('id', (int) $itemid);
        $parents = $role->getParents();
        //CHECKME: the better way would be to have the default primary parent modvar be null, rather than Everybody
        // then this looping would be unnecessary
        $validparent = false;
        foreach ($parents as $parent) {
            if ($parentid == $parent->getID()) {
                $validparent = true;
            }
        }
        if (!$validparent) {
            $parentid = $parents[0]->getID();
        }

        return $parentid;
    }
}
