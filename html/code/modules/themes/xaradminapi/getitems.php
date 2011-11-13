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

    if (!is_array($sort))
        $sort = strpos($sort, ',') !== false ? array_map('trim', explode(',', $sort)) : array(trim($sort));
    foreach ($sort as $pairs) {
        list($sortfield, $sortorder) = array_map('trim', array_pad(explode(' ', $pairs), 2, 'ASC'));
        if (!isset($select[$sortfield]) || isset($orderby[$sortfield])) continue;
        $orderby[$sortfield] = $select[$sortfield] . ' ' . strtoupper($sortorder);
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

    $items = array();
    while($result->next()) {
        $item = array();
        foreach (array_keys($select) as $field)
            $item[$field] = array_shift($result->fields);

        if (xarVarIsCached('Theme.Infos', $item['regid'])) {
            // merge cached info with db info 
            $item += xarVarGetCached('Theme.Infos', $item['regid']);
        } else {
            $item['displayname'] = $item['name'];
            // Shortcut for os prepared directory 
            $item['osdirectory'] = xarVarPrepForOS($item['directory']);
            
            xarVarSetCached('Theme.BaseInfos', $item['name'], $item);                   

            $fileinfo = xarTheme_getFileInfo($item['osdirectory']);
            if (isset($fileinfo)) {
                $item = array_merge($fileinfo, $item);
                xarVarSetCached('Theme.Infos', $item['regid'], $item);
                switch ($item['state']) {
                case XARTHEME_STATE_MISSING_FROM_UNINITIALISED:
                    $item['state'] = XARTHEME_STATE_UNINITIALISED;
                    break;
                case XARTHEME_STATE_MISSING_FROM_INACTIVE:
                    $item['state'] = XARTHEME_STATE_INACTIVE;
                    break;
                case XARTHEME_STATE_MISSING_FROM_ACTIVE:
                    $item['state'] = XARTHEME_STATE_ACTIVE;
                    break;
                case XARTHEME_STATE_MISSING_FROM_UPGRADED:
                    $item['state'] = XARTHEME_STATE_UPGRADED;
                    break;
                }
            } else {
                // There was an entry in the database which was not in the file system,
                // @CHECKME: do we really want to do this here? 
                // <chris/> I think not, this is handled by refresh and managed in list UI 
                /*
                // This functionality was present in getlist api function
                // remove the entry from the database
                xarMod::apiFunc('themes', 'admin', 'remove', array('regid' => $item['regid']));
                continue;
                */
                // <chris/> instead, let's apply the correct state and pass through 
                // This functionality was present in getthemelist api 
                // Following changes were applied by <andyv> on 21st May 2003
                // as per the patch by Garrett Hunter
                // Credits: Garrett Hunter <Garrett.Hunter@Verizon.net>
                switch ($item['state']) {
                case XARTHEME_STATE_UNINITIALISED:
                    $item['state'] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
                    break;
                case XARTHEME_STATE_INACTIVE:
                    $item['state'] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
                    break;
                case XARTHEME_STATE_ACTIVE:
                    $item['state'] = XARTHEME_STATE_MISSING_FROM_ACTIVE;
                    break;
                case XARTHEME_STATE_UPGRADED:
                    $item['state'] = XARTHEME_STATE_MISSING_FROM_UPGRADED;
                    break;
                }
                //$item['class'] = "";
                $item['version'] = "&#160;";

            }
        }
        
        $items[] = $item;
    }
    $result->close();
    
    return $items;
}
?>