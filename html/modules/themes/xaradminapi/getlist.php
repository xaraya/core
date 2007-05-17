<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 */

/**
 * Gets a list of themes that matches required criteria.
 *
 * Supported criteria are UserCapable, AdminCapable, Class, Category, State.
 *
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
 * @return array of theme information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getlist($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes',
                                     'class' => 'infos');
    if (!is_array($filter)) {
        throw new BadParameterException('filter','Parameter filter must be an array.');
    }

    // Optional arguments.
    if (!isset($startNum)) {
        $startNum = 1;
    }
    if (!isset($numItems)) {
        $numItems = -1;
    }

    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            throw new BadParameterException('orderBy','Parameter orderBy can contain only \'name\' or \'regid\' or \'class\' as items.');
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);

    // Determine the right tables to use
    $dbconn = xarDB::getConn();
    $tables = xarDB::getConn();
    $themestable = $tables['themes'];

    // Construct an array with where conditions and their bind variables
    $whereClauses = array(); $bindvars = array();

    if (isset($filter['Class'])) {
        $whereClauses[] = 'themes.class = ?';
        $bindvars[] = $filter['Class'];
    }
    if (isset($filter['State'])) {
        if ($filter['State'] != XARTHEME_STATE_ANY) {
            $whereClauses[] = 'themes.state = ?';
            $bindvars[] = $filter['State'];
        }
    } else {
        $whereClauses[] = 'themes.state = ?';
        $bindvars[] = XARTHEME_STATE_ACTIVE;
    }

    $themeList = array();

    $whereClause = '';
    if (!empty($whereClauses)) {
        $whereClause = 'WHERE ' . join(' AND ', $whereClauses);

    }
    $query = "SELECT themes.regid,
                     themes.name,
                     themes.directory,
                     themes.state
              FROM $tables[themes] AS themes $whereClause ORDER BY $orderByClause";

    $stmt = $dbconn->prepareStatement($query);
    $stmt->setLimit($numItems);
    $stmt->setOffset($startNum - 1);
    $result = $stmt->executeQuery($bindvars);

    while($result->next()) {
        list($themeInfo['regid'],
             $themeInfo['name'],
             $themeInfo['directory'],
             $themeState) = $result->fields;

        if (xarVarIsCached('Theme.Infos', $themeInfo['regid'])) {
            // Get infos from cache
            $themeList[] = xarVarGetCached('Theme.Infos', $themeInfo['regid']);
        } else {
            $themeInfo['displayname'] = $themeInfo['name'];
            // Shortcut for os prepared directory
            $themeInfo['osdirectory'] = xarVarPrepForOS($themeInfo['directory']);

            $themeInfo['state'] = (int) $themeState;

            xarVarSetCached('Theme.BaseInfos', $themeInfo['name'], $themeInfo);

            $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
            if (!isset($themeFileInfo)) {
                // There was an entry in the database which was not in the file system,
                // remove the entry from the database
                xarModAPIFunc('themes','admin','remove',array('regid' => $themeInfo['regid']));
            } else {
                $themeInfo = array_merge($themeInfo, $themeFileInfo);
                xarVarSetCached('Theme.Infos', $themeInfo['regid'], $themeInfo);
                $themeList[] = $themeInfo;
            }
        }
        $themeInfo = array();
    }
    $result->close();
    return $themeList;
}
?>
