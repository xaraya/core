<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminApi;
use EmptyParameterException;
use VariableValidationException;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges adminapi get function
 * @extends MethodClass<AdminApi>
 */
class GetMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get a specific privilege
     * @todo Transient hack, will be removed
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::get()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($itemid) && empty($name)) {
            throw new EmptyParameterException('itemid or name');
        } elseif (!empty($itemid) && !is_numeric($itemid)) {
            throw new VariableValidationException(['itemid',$itemid,'numeric']);
        }

        $xartable = $this->db()->getTables();
        $query = "SELECT p.id, p.name, p.realm_id,
                         m.regid, p.component, p.instance,
                         p.level,  p.description
                  FROM " . $xartable['privileges'] . " p
                  LEFT JOIN " . $xartable['modules'] . " m ON p.module_id = m.id
                  WHERE p.itemtype = " . xarSecurity::PRIVILEGES_PRIVILEGETYPE;
        if (isset($itemid)) {
            $query .= " AND p.id = " . $itemid;
        }
        if (isset($name)) {
            $query .= " AND p.name = " . $name;
        }
        $dbconn = $this->db()->getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery();
        $privilege = [];
        if ($result->next()) {
            [$id, $name, $realm, $regid, $component, $instance, $level,
                $description] = $result->fields;
            $privilege = ['id' => $id,
                'name' => $name,
                'realm' => $realm,
                'moduleid' => $regid,
                'component' => $component,
                'instance' => $instance,
                'level' => $level,
                'description' => $description];
        }

        return $privilege;
    }
}
