<?php
/**
 * Gets a list of themes 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Gets a list of themes that matches required criteria.
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category, State.
 * @author original - Marco Canini <marco@xaraya.com>, 
 * @author andyv - modified
 * @param filter array of criteria used to filter the entire list of installed themes.
 * @param startNum the start offset in the list
 * @param numItems the length of the list
 * @param orderBy the order type of the list
 * @return array array of theme information arrays
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getthemelist($args)
{
    extract($args);

    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes', 'class' => 'infos');

    if (!isset($filter)) $filter = array();
    if (!is_array($filter)) {
        throw new BadParameterException('filter','The parameter #(1) must be an array.');
    }

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;
    if (!isset($orderBy)) $orderBy = 'name';

    // Construct order by clause
    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            throw new BadParameterException('orderBy','The parameter #(1) can contain only \'name\' or \'regid\' or \'class\' as items.');
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);
    
    // Determine the tables we are going to use
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    $themestable = $tables['themes'];
    $theme_statesTables = array($tables['system/theme_states'], $tables['site/theme_states']);

    // Construct arrays for the where conditions and their bind variables
    $whereClauses = array(); $bindvars = array();
    if (isset($filter['Mode'])) {
        $whereClauses[] = 'themes.xar_mode = ?';
        $bindvars[] = $filter['Mode'];
    }

    if (isset($filter['Class'])) {
        $whereClauses[] = 'themes.xar_class = ?';
        $bindvars[] = $filter['Class'];
    }

    if (isset($filter['State'])) {
        if ($filter['State'] != XARTHEME_STATE_ANY) {
            if ($filter['State'] != XARTHEME_STATE_INSTALLED) {
                $whereClauses[] = 'states.xar_state = ?';
                $bindvars[] = $filter['State'];
            } else {
                $whereClauses[] = 'states.xar_state != ? AND states.xar_state < ? AND states.xar_state != ?';
                $bindvars[] = XARTHEME_STATE_UNINITIALISED;
                $bindvars[] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'states.xar_state = ?';
        $bindvars[] = XARTHEME_STATE_ACTIVE;
    }


    $mode = XARTHEME_MODE_SHARED;
    $themeList = array();

    // Here we do 2 SELECTs: one for SHARED moded themes and
    // one for PER_SITE moded themes
    // Maybe this could be done with a single query?
    for ($i = 0; $i < 1; $i++ ) {
        $theme_statesTable = $theme_statesTables[$i];

        $query = "SELECT themes.xar_regid,
                         themes.xar_name,
                         themes.xar_directory,
                         themes.xar_class,
                         states.xar_state
                  FROM $themestable AS themes
                  LEFT JOIN $theme_statesTable AS states 
                    ON themes.xar_regid = states.xar_regid";       
        array_unshift($whereClauses, 'themes.xar_mode = ?');
        array_unshift($bindvars,$mode);

        $whereClause = join(' AND ', $whereClauses);
        if($whereClause != ''){
        $query .= " WHERE $whereClause";
        }
        $query .= " ORDER BY $orderByClause";
        
        $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1,$bindvars);

        while(!$result->EOF) {
            list($themeInfo['regid'],
                 $themeInfo['name'],
                 $themeInfo['directory'],
                 $themeInfo['class'],
                 $themeState) = $result->fields;

            if (xarVarIsCached('Theme.Infos', $themeInfo['regid'])) {
                // Get infos from cache
                $themeList[] = xarVarGetCached('Theme.Infos', $themeInfo['regid']);
            } else {
                $themeInfo['mode'] = (int) $mode;
                $themeInfo['displayname'] = xarThemeGetDisplayableName($themeInfo['name']);
                // Shortcut for os prepared directory
                $themeInfo['osdirectory'] = xarVarPrepForOS($themeInfo['directory']);

                $themeInfo['state'] = (int) $themeState;

                xarVarSetCached('Theme.BaseInfos', $themeInfo['name'], $themeInfo);

                $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
                if (!isset($themeFileInfo)) {
                    // Following changes were applied by <andyv> on 21st May 2003
                    // as per the patch by Garrett Hunter
                    // Credits: Garrett Hunter <Garrett.Hunter@Verizon.net>
                    switch ($themeInfo['state']) {
                        case XARTHEME_STATE_UNINITIALISED:
                            $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
                            break;
                        case XARTHEME_STATE_INACTIVE:
                            $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
                            break;
                        case XARTHEME_STATE_ACTIVE:
                            $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_ACTIVE;
                            break;
                        case XARTHEME_STATE_UPGRADED:
                            $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_UPGRADED;
                            break;
                    }
                    //$themeInfo['class'] = "";
                    $themeInfo['version'] = "&nbsp;";
                    // end patch
                }
                $themeInfo = array_merge($themeInfo, $themeFileInfo);

                xarVarSetCached('Theme.Infos', $themeInfo['regid'], $themeInfo);

                $themeList[] = $themeInfo;
            }
            $themeInfo = array();
            $result->MoveNext();
        }
        $result->Close();

        $mode = XARTHEME_MODE_PER_SITE;
        array_shift($whereClauses);
        array_shift($bindvars);
    }

    return $themeList;
}

?>
