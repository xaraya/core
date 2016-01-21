<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/1.html
 */
function modules_adminapi_getitems(Array $args=array())
{
    extract($args);
    
    if (!isset($state))
        $state = XARMOD_STATE_ACTIVE;
    
    if (!isset($include_core))
        $include_core = true;
    
    if (!isset($sort))
        $sort = 'name ASC';
        
    // Determine the tables we are going to use
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $modules_table = $tables['modules'];
    
    $select = array();
    $where = array();
    $orderby = array();
    $bindvars = array();

    $select['id']            = 'mods.id';
    $select['regid']         = 'mods.regid';
    $select['name']          = 'mods.name';
    $select['directory']     = 'mods.directory';
    $select['version']       = 'mods.version';
    $select['systemid']      = 'mods.id';
    $select['class']         = 'mods.class';
    $select['category']      = 'mods.category';
    $select['state']         = 'mods.state';
    $select['user_capable']  = 'mods.user_capable';
    $select['admin_capable'] = 'mods.admin_capable';    

    if (!empty($regid)) {
        $where[] = 'mods.regid = ?';
        $bindvars[] = $regid;
    }    
    
    if (!empty($name)) {
        if (is_array($name)) {
            $where[] = 'mods.name IN (' . implode(',', array_fill(0, count($name), '?')) . ')';
            $bindvars = array_merge($bindvars, $name);
        } else {             
            $where[] = 'mods.name = ?';
            $bindvars[] = $name;
        }
    }
    
    if (!empty($systemid)) {
        $where[] = 'mods.id = ?';
        $bindvars[] = $systemid;
    }  

    if ($state != XARMOD_STATE_ANY) {
        if ($state != XARMOD_STATE_INSTALLED) {
            $where[] = 'mods.state = ?';
            $bindvars[] = $state;
        } else {
            $where[] = 'mods.state != ? AND mods.state < ? AND mods.state != ?';
            $bindvars[] = XARMOD_STATE_UNINITIALISED;
            $bindvars[] = XARMOD_STATE_MISSING_FROM_INACTIVE;
            $bindvars[] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
        }    
    }
    
    if (!empty($modclass)) {
        $where[] = 'mods.class = ?';
        $bindvars[] = $modclass;
    }
    
    if (!empty($category)) {
        $where[] = 'mods.category = ?';
        $bindvars[] = $category;
    }
    
    if (!$include_core) {
        $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories');
        $where[] = 'mods.name NOT IN (' . implode(',', array_fill(0, count($coremods), '?')) . ')';
        $bindvars = array_merge($bindvars, $coremods);        
    }

    if (isset($user_capable)) {
        $where[] = 'mods.user_capable = ?';
        $bindvars[] = (bool) $user_capable;
    }

    if (isset($admin_capable)) {
        $where[] = 'mods.admin_capable = ?';
        $bindvars[] = (bool) $admin_capable;
    }  

    if (!is_array($sort))
        $sort = strpos($sort, ',') !== false ? array_map('trim', explode(',', $sort)) : array(trim($sort));
    foreach ($sort as $pairs) {
        list($sortfield, $sortorder) = array_map('trim', array_pad(explode(' ', $pairs), 2, 'ASC'));
        if (!isset($select[$sortfield]) || isset($orderby[$sortfield])) continue;
        $orderby[$sortfield] = $select[$sortfield] . ' ' . strtoupper($sortorder);
    }
    
    $query = "SELECT " . join(',', $select);
    $query .= " FROM $modules_table mods";
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
    while ($result->next()) {
        $item = array();
        foreach (array_keys($select) as $field)
            $item[$field] = array_shift($result->fields);

        if (xarVarIsCached('Mod.Infos', $item['regid'])) {
            // merge cached info with db info 
            $item += xarVarGetCached('Mod.Infos', $item['regid']);
        } else {
            $item['displayname'] = xarModGetDisplayableName($item['name']);
            $item['displaydescription'] = xarModGetDisplayableDescription($item['name']);
            // Shortcut for os prepared directory
            $item['osdirectory'] = xarVarPrepForOS($item['directory']);

            xarVarSetCached('Mod.BaseInfos', $item['name'], $item);            
                   
            $fileinfo = xarMod_getFileInfo($item['osdirectory']);
            if (isset($fileinfo)) {
                $item = array_merge($fileinfo, $item);
                xarVarSetCached('Mod.Infos', $item['regid'], $item);
                switch ($item['state']) {
                case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
                    $item['state'] = XARMOD_STATE_UNINITIALISED;
                    break;
                case XARMOD_STATE_MISSING_FROM_INACTIVE:
                    $item['state'] = XARMOD_STATE_INACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_ACTIVE:
                    $item['state'] = XARMOD_STATE_ACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_UPGRADED:
                    $item['state'] = XARMOD_STATE_UPGRADED;
                    break;
                }
            }        
        }
        $items[] = $item;    
    }
    $result->close();
        
    return $items;
}
?>