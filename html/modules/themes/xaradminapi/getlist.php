<?php
/**
 * File: $Id$
 *
 * Themes administration
 *
 * @package modules
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage themes
 * @author Marty Vance
*/

/**
 * Gets a list of themes that matches required criteria.
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category,
 * State.
 * Permitted values for Mode are XARTHEME_MODE_SHARED and XARTHEME_MODE_PER_SITE.
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed themes that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in the proper
 * RFC.
 * Permitted values for State are XARTHEME_STATE_ANY, XARTHEME_STATE_UNINITIALISED,
 * XARTHEME_STATE_INACTIVE, XARTHEME_STATE_ACTIVE, XARTHEME_STATE_MISSING,
 * XARTHEME_STATE_UPGRADED.
 * The XARTHEME_STATE_ANY means that any state is valid.
 * The default value of State is XARTHEME_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco.canini@postnuke.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        themes.
 * @param startNum the start offset in the list
 * @param numItems the length of the list
 * @param orderBy the order type of the list
 * @returns array
 * @return array of theme information arrays
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getlist($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes',
                                     'class' => 'infos');
    if (!is_array($filter)) {
        $msg = xarML('Parameter filter must be an array.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments.
    if (!isset($startNum)) {
        $startNum = 1;
    }
    if (!isset($numItems)) {
        $numItems = -1;
    }

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
    $tables = xarDBGetTables();

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
    for ($i = 0; $i < 2; $i++ ) {
        $theme_statesTable = $theme_statesTables[$i];

        $query = "SELECT themes.xar_regid,
                         themes.xar_name,
                         themes.xar_directory,
                         states.xar_state";

        $query .= " FROM $tables[themes] AS themes";
        array_unshift($whereClauses, 'themes.xar_mode = '.$mode);

        // Do join
        $query .= " LEFT JOIN $theme_statesTable AS states ON themes.xar_regid = states.xar_regid";

        $whereClause = join(' AND ', $whereClauses);
        $query .= " WHERE $whereClause";

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
                if (!isset($themeFileInfo)) return; // throw back
                $themeInfo = array_merge($themeInfo, $themeFileInfo);

                xarVarSetCached('Theme.Infos', $themeInfo['regid'], $themeInfo);

                $themeList[] = $themeInfo;
            }
            $themeInfo = array();
            $result->MoveNext();
        }
        $result->Close();

        $mode = XARTHEME_MODE_PER_SITE;
        array_shift($whereClauses);break;
    }
    return $themeList;
}

?>