<?php
/**
 * File: $Id$
 *
 * Get a list of themes that match required criteria
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
/**
 * Gets a list of themes that matches required criteria.
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category, State.
 * @author original - Marco Canini <marco@xaraya.com>, modified <andyv>
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
        $msg = xarML('Parameter filter must be an array.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;
    if (!isset($orderBy)) $orderBy = 'name';

    $extraSelectClause = '';
    $whereClauses = array();

    $orderFields = explode('/', $orderBy);
    $orderByClauses = array();
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            $msg = xarML('Parameter orderBy can contain only \'name\' or \'regid\' or \'class\' as items.');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }

    list($dbconn) = xarDBGetConn();
    $tables =& xarDBGetTables();
    $themestable = $tables['themes'];

    $theme_statesTables = array($tables['system/theme_states'], $tables['site/theme_states']);

    if (isset($filter['Mode'])) {
        $whereClauses[] = 'themes.xar_mode = '.xarVarPrepForStore($filter['Mode']);
    }

    if (isset($filter['Class'])) {
        $whereClauses[] = 'themes.xar_class = '.xarVarPrepForStore($filter['Class']);
    }

    if (isset($filter['State'])) {
        if ($filter['State'] != XARTHEME_STATE_ANY) {
            $whereClauses[] = 'states.xar_state = '.xarVarPrepForStore($filter['State']);
        }
    } else {
        $whereClauses[] = 'states.xar_state = '.XARTHEME_STATE_ACTIVE;
    }

    $orderByClause = join(', ', $orderByClauses);

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
                         states.xar_state";


        $query .= " FROM $themestable AS themes";
        array_unshift($whereClauses, 'themes.xar_mode = '.$mode);

        // Do join
        $query .= " LEFT JOIN $theme_statesTable AS states ON themes.xar_regid = states.xar_regid";

        $whereClause = join(' AND ', $whereClauses);
        if($whereClause != ''){
        $query .= " WHERE $whereClause";
        }

        $query .= " ORDER BY $orderByClause";
        $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1);
        if (!$result) return;

        while(!$result->EOF) {
            list($themeInfo['regid'],
                 $themeInfo['name'],
                 $themeInfo['directory'],
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
                    $themeInfo['state'] = (int) XARTHEME_STATE_MISSING;
                    $themeInfo['class'] = "";
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
    }

    return $themeList;
}

