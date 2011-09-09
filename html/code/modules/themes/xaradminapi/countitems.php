<?php
function themes_adminapi_countitems(Array $args=array())
{
    extract($args);

    if (!isset($state))
        $state = XARTHEME_STATE_ACTIVE;

    if (!isset($class))
        $class = 3; // any
        
    // Determine the tables we are going to use
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $themes_table = $tables['themes'];

    $where = array();
    $bindvars = array();

    if ($state != XARTHEME_STATE_ANY) {
        if ($state != XARTHEME_STATE_INSTALLED) {
            $where[] = 'themes.state = ?';
            $bindvars[] = $state;
        } else {
            $where[] = 'themes.state != ? AND themes.state < ? AND themes.state != ?';
            $bindvars[] = XARTHEME_STATE_UNINITIALISED;
            $bindvars[] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
            $bindvars[] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
        }
    }
    if (isset($class) && $class != 3) {
        $where[] = 'themes.class = ?';
        $bindvars[] = $class;
    }
    // build query
    $query = "SELECT COUNT(themes.id)"; 
    $query .= " FROM $themes_table themes";
    if (!empty($where))
        $query .= ' WHERE ' . join(' AND ', $where);    
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;    
    list($count) = $result->fields;

    $result->Close();
    
    return $count;    

}
?>