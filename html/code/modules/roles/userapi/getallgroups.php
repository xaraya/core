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
use Query;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getallgroups function
 * @extends MethodClass<UserApi>
 */
class GetallgroupsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * viewallgroups - generate all groups listing.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array|void listing of available groups
     * @todo this code is unreadable
     * @see UserApi::getallgroups()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $xartable = $this->db()->getTables();

        // Security Check
        if (!$this->sec()->checkAccess('ViewRoles')) {
            return;
        }

        if (!isset($show_top)) {
            $show_top = 0;
        }

        sys::import('xaraya.structures.query');

        if (!isset($ancestor)) {
            $q = new Query('SELECT');
            $q->addtable($xartable['roles'], 'r');
            $q->addtable($xartable['rolemembers'], 'rm');
            $q->leftjoin('r.id', 'rm.role_id');
            $q->addfields(['r.id AS id','r.name AS name','r.users AS users','rm.parent_id AS parentid']);
            $conditions = [];

            // Restriction by group
            if (isset($group)) {
                $groups = explode(',', $group);
                foreach ($groups as $group) {
                    $conditions[] = $q->peq('r.name', trim($group));
                }
            }

            // Restriction by parent group
            if (isset($parent)) {
                $groups = explode(',', $parent);
                foreach ($groups as $group) {
                    $group = $userapi->get(
                        [
                            (is_numeric($group) ? 'id' : 'name') => trim($group),
                            'itemtype' => xarRoles::ROLES_GROUPTYPE,
                        ]
                    );
                    if (isset($group['id']) && is_numeric($group['id'])) {
                        $conditions[] = $q->peq('rm.parent_id', $group['id']);
                    }
                    if ($show_top) {
                        $conditions[] = $q->peq('r.id', $group['id']);
                    }
                }
            }

            if (count($conditions) != 0) {
                $q->qor($conditions);
            }
            $q->eq('r.itemtype', xarRoles::ROLES_GROUPTYPE);
            $q->ne('r.state', xarRoles::ROLES_STATE_DELETED);
            // Apparently this is not needed
            //        $q->setgroup('r.id');

            $q->run();
            return $q->output();

        } else {
            // Restriction by ancestor group. This option supports group IDs or names.

            // Get all the groups
            $q = new Query('SELECT');
            $q->addtable($xartable['roles'], 'r');
            $q->addtable($xartable['rolemembers'], 'rm');
            $q->leftjoin('r.id', 'rm.role_id');
            $q->addfields(['r.id','r.name','r.users','rm.parent_id']);
            $q->eq('r.itemtype', xarRoles::ROLES_GROUPTYPE);
            $q->run();
            $allgroups = [];
            $result = $q->output();
            foreach ($result as $row) {
                $allgroups[$row['name']] = $row;
            }

            // Make sure we have IDs for all inputs
            $groups = explode(',', $ancestor);
            $ids = [];
            foreach ($groups as $group) {
                $group = trim($group);
                if (is_numeric($group)) {
                    $ids[] = $group;
                } else {
                    if (isset($allgroups[$group])) {
                        $ids[] = (int) $allgroups[$group]['id'];
                    }
                }
            }

            // Do we include the parent(s)?
            $descendants = [];
            if ($show_top) {
                foreach ($ids as $id) {
                    foreach ($allgroups as $group) {
                        if ((int) $group['id'] == $id) {
                            $descendants[$id] = $group;
                            break;
                        }
                    }
                }
            }
            // Run the recursion
            foreach ($ids as $id) {
                $subgroups = array_merge($descendants, $this->recursive_getDescendants($id, $allgroups));
                foreach ($subgroups as $subgroup) {
                    $descendants[$subgroup['id']] = $subgroup;
                }
            }
            return $descendants;
        }
    }

    protected function recursive_getDescendants($ancestor, &$allgroups)
    {
        $descendants = [];
        foreach ($allgroups as $group) {
            if ($group['parent_id'] == $ancestor) {
                $descendants[$group['id']] = $group;
            }
        }
        $subgroups = [];
        foreach ($descendants as $descendant) {
            $subgroups = $this->recursive_getDescendants((int) $descendant['id'], $allgroups);
            foreach ($subgroups as $subgroup) {
                $subgroups[$subgroup['id']] = $subgroup;
            }
            $descendants = array_merge($descendants, $subgroups);
        }
        return $descendants;
    }
}
