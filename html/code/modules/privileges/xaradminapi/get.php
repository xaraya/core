<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 */

/**
 * Get a specific privilege
 * Transient hack, will be removed
 * @param array    $args array of optional parameters<br/>
 */
function privileges_adminapi_get(Array $args=array())
{
    extract($args);
    if (empty($itemid) && empty($name)) {
        throw new EmptyParameterException('itemid or name');
    } elseif (!empty($itemid) && !is_numeric($itemid)) {
        throw new VariableValidationException(array('itemid',$itemid,'numeric'));
    }

    $xartable = xarDB::getTables();
    $query = "SELECT p.id, p.name, p.realm_id,
                     m.regid, p.component, p.instance,
                     p.level,  p.description
              FROM " . $xartable['privileges'] . " p
              LEFT JOIN ". $xartable['modules'] . " m ON p.module_id = m.id
              WHERE p.itemtype = " . xarSecurity::PRIVILEGES_PRIVILEGETYPE;
    if (isset($itemid)) {
        $query .= " AND p.id = " . $itemid;
    }
    if (isset($name)) {
        $query .= " AND p.name = " . $name;
    }
    $dbconn = xarDB::getConn();
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery();
    $privilege = array();
    if ($result->next()) {
        list($id, $name, $realm, $regid, $component, $instance, $level,
                $description) = $result->fields;
        $privilege = array('id' => $id,
                       'name' => $name,
                       'realm' => $realm,
                       'moduleid' => $regid,
                       'component' => $component,
                       'instance' => $instance,
                       'level' => $level,
                       'description' => $description);
    }

    return $privilege;
}

?>