<?php
/**
 * Get a specific privilege
 * Transient hack, will be removed
 */
function privileges_adminapi_get($args)
{
    extract($args);
    if (empty($itemid) && empty($name)) {
        throw new EmptyParameterException('itemid or name');
    } elseif (!empty($itemid) && !is_numeric($itemid)) {
        throw new VariableValidationException(array('itemid',$itemid,'numeric'));
    }

    $xartable =& xarDBGetTables();
    $query = "SELECT p.id, p.name, p.realmid,
                     m.xar_regid, p.component, p.instance,
                     p.level,  p.description
              FROM " . $xartable['privileges'] . " p
              LEFT JOIN ". $xartable['modules'] . " m ON p.module_id = m.xar_id
              WHERE p.type = " . xarMasks::PRIVILEGES_PRIVILEGETYPE;
    if (isset($itemid)) {
        $query .= " AND p.id = " . $itemid;
    }
    if (isset($name)) {
        $query .= " AND p.name = " . $name;
    }
    $dbconn =& xarDBGetConn();
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
