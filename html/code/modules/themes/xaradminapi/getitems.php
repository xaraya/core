<?php
/**
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com 
 */

function themes_adminapi_getitems(Array $args=array())
{
    extract($args);

    if (!isset($state))
        $state = XARTHEME_STATE_ACTIVE;

    if (!isset($class))
        $class = 3; // any
    
    if (!isset($sort))
        $sort = 'name ASC';
        
    // Determine the tables we are going to use
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $themes_table = $tables['themes'];
    
    $select = array();
    $where = array();
    $orderby = array();
    $bindvars = array();
    
    $select['regid'] = 'themes.regid';
    $select['name'] = 'themes.name';
    $select['directory'] = 'themes.directory';
    $select['state'] = 'themes.state';
    $select['class'] = 'themes.class';

    if (isset($name)) {
        $where[] = 'themes.name = ?';
        $bindvars[] = $name;
    }
    
    if (isset($regid)) {
        $where[] = 'themes.regid = ?';
        $bindvars[] = $regid;
    }
    
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

    $sorts = strpos($sort, ',') !== false ? explode(',', $sort) : array($sort);
    foreach ($sorts as $pairs) {
        $pair = explode(' ', $pairs);
        $sortfield = trim($pair[0]);
        if (!isset($select[$sortfield])) continue;
        $sortorder = isset($pair[1]) ? trim(strtoupper($pair[1])) : 'ASC';
        $orderby[] = $select[$sortfield] . ' ' . $sortorder;
    }

    $query = "SELECT " . join(',', $select);
    $query .= " FROM $themes_table themes";
    if (!empty($where))
        $query .= " WHERE " . join(' AND ', $where);
    if (!empty($orderby))
        $query .= " ORDER BY " . join(',', $orderby);

    $stmt = $dbconn->prepareStatement($query);
    if (!empty($numitems)) {
        $stmt->setLimit($numitems);
        if (empty($startnum))
            $startnum = 1;
        $stmt->setOffset($startnum - 1);
    }
    $result = $stmt->executeQuery($bindvars);

    $themes = array();
    while($result->next()) {
        $theme = array();
        list($theme['regid'], 
            $theme['name'], 
            $theme['directory'], 
            $theme['state'], 
            $theme['class']) = $result->fields;
        $theme['osdirectory'] = xarVarPrepForOS($theme['directory']);
        $info = xarTheme_getFileInfo($theme['osdirectory']);
        if (!$info) continue;
        $theme += $info;
        $themes[] = $theme;
    }
    $result->close();
    
    return $themes;
}
?>