<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
function themes_adminapi_countitems(Array $args=array())
{
    extract($args);

    if (!isset($state))
        $state = xarTheme::STATE_ACTIVE;

    if (!isset($class))
        $class = 3; // any
        
    // Determine the tables we are going to use
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $themes_table = $tables['themes'];

    $where = array();
    $bindvars = array();

    if ($state != xarTheme::STATE_ANY) {
        if ($state != xarTheme::STATE_INSTALLED) {
            $where[] = 'themes.state = ?';
            $bindvars[] = $state;
        } else {
            $where[] = 'themes.state != ? AND themes.state < ? AND themes.state != ?';
            $bindvars[] = xarTheme::STATE_UNINITIALISED;
            $bindvars[] = xarTheme::STATE_MISSING_FROM_INACTIVE;
            $bindvars[] = xarTheme::STATE_MISSING_FROM_UNINITIALISED;
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