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
use DataObjectFactory;
use Query;
use xarDB;
use xarMod;
use xarModVars;
use xarRoles;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getallroles function
 * @extends MethodClass<UserApi>
 */
class GetallrolesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get all roles
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['order'] comma-separated list of order items; default 'name'<br/>
     * string   $args['selection'] extra coonditions passed into the where-clause<br/>
     * string   $args['include'] comma-separated list of role names<br/>
     * string   $args['exclude'] comma-separated list of role names
     * @return mixed array of roles, or false on failure
     * @see UserApi::getallroles()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('ReadRoles')) {
            return;
        }
        extract($args);

        // Optional arguments.
        if (!isset($startnum)) {
            $startnum = 1;
        }
        if (!isset($numitems)) {
            $numitems = (int) $this->mod()->getVar('items_per_page');
        }

        sys::import('xaraya.structures.query');
        $q = new Query();
        $xartable = $this->db()->getTables();
        $q->addtable($xartable['roles'], 'r');

        // Order
        if (!isset($order)) {
            $q->addorder('r.name');
        } else {
            foreach (explode(',', $order) as $order_field) {
                if (preg_match('/^[-]?(name|uname|email|id|state|date_reg)$/', $order_field)) {
                    if (strstr($order_field, '-')) {
                        $q->addorder('r.' . $order_field, 'DESC');
                    } else {
                        $q->addorder('r.' . $order_field);
                    }
                }
            }
        }

        // Itemtype
        if (!empty($itemtype)) {
            $q->eq('r.itemtype', $itemtype);
        }

        // State
        if (!empty($state) && is_numeric($state) && $state != xarRoles::ROLES_STATE_CURRENT) {
            $q->eq('r.state', $state);
        } else {
            $q->ne('r.state', xarRoles::ROLES_STATE_DELETED);
        }

        $q->addfield('r.id AS id');
        $q->addfield('r.name AS name');
        $q->addfield('r.itemtype AS itemtype');
        $q->addfield('r.users AS users');
        $q->addfield('r.uname AS uname');
        $q->addfield('r.pass AS pass');
        $q->addfield('r.email AS email');
        $q->addfield('r.date_reg AS date_reg');
        $q->addfield('r.state AS state');
        $q->addfield('r.valcode AS valcode');
        $q->addfield('r.auth_module_id AS auth_module_id');

        // Inclusions
        $includedgroups = [];
        if (isset($include)) {
            foreach (explode(',', $include) as $include_field) {
                if ($itemtype == xarRoles::ROLES_USERTYPE) {
                    $q->ne('uname', $userapi->get(['uname' => $include_field]));
                } elseif ($itemtype == xarRoles::ROLES_GROUPTYPE) {
                    $q->ne('name', $userapi->get(['name' => $include_field]));
                    $includedgroups[] = $include_field;
                }
            }
        }

        // Exclusions
        $excludedgroups = [];
        if (isset($exclude)) {
            foreach (explode(',', $exclude) as $exclude_field) {
                if ($itemtype == xarRoles::ROLES_USERTYPE) {
                    $q->ne('uname', $userapi->get(['uname' => $exclude_field]));
                } elseif ($itemtype == xarRoles::ROLES_GROUPTYPE) {
                    $q->ne('name', $userapi->get(['name' => $exclude_field]));
                    $excludedgroups[] = $exclude_field;
                }
            }
        }

        if ($includedgroups != [] || $excludedgroups != []) {
            $q->addtable($xartable['rolemembers'], 'rm');
            $q->join('r.id', 'rm.role_id');
            foreach ($includedgroups as $include) {
                $q->eq('rm.parent_id', $include);
            }
            foreach ($excludedgroups as $exclude) {
                $q->ne('rm.parent_id', $exclude);
            }
        }

        // cfr. cachemanager - this approach might change later
        $expire = $this->mod()->getVar('cache.userapi.getallroles');
        if (!empty($expire)) {
            $expire = unserialize($expire);
            $q = $expire;
        }

        if ($startnum == 0) {
            $q->setstartat($startnum);
            $q->setrowstodo($numitems);
        }
        if (!$q->run()) {
            return;
        }
        $items['nativeitems'] = $q->output();
        $itemids = [];
        foreach ($items['nativeitems'] as $item) {
            $itemids[] = $item['id'];
        }
        switch ($itemtype) {
            case 1: $name = "roles_users";
                break;
            case 2: $name = "roles_groups";
                break;
        }
        $object = $this->data()->getObjectList(['name' => $name]);
        $items['dditems'] = $object->getItems(['itemids' => $itemids,'getobject' => true]);
        /*    for ($i = 0, $max = count($items); $i < $max; $i++) {
                if (!isset($properties[$items[$i]['id']])) continue;
                $items[$i] = array_merge($items[$i],$properties[$items[$i]['id']]);
            }
        */    return $items;
    }
}
