<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 */

/**
 * Get a list of modules that matches required criteria.
 *
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category,
 * State.
 * Permitted values for Mode are XARMOD_MODE_SHARED and XARMOD_MODE_PER_SITE.
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed modules that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in RFC 13.
 *
 * Permitted values for State are XARMOD_STATE_ANY, XARMOD_STATE_UNINITIALISED,
 * XARMOD_STATE_INACTIVE, XARMOD_STATE_ACTIVE, XARMOD_STATE_MISSING,
 * XARMOD_STATE_UPGRADED, XARMOD_STATE_INSTALLED
 * The XARMOD_STATE_ANY means that any state is valid.
 * The default value of State is XARMOD_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco@xaraya.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        modules.
 * @param startNum integer the start offset in the list
 * @param numItems integer the length of the list
 * @param orderBy string the order type of the list
 * @return array array of module information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function modules_adminapi_getlist($args)
{
    extract($args);
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods','id' => 'mods',
                                     'class' => 'mods', 'category' => 'mods');

    if (!isset($filter)) $filter = array();

    if (!is_array($filter)) throw new BadParameterException('filter','Parameter filter must be an array.');

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;
    if (!isset($orderBy)) $orderBy = 'name';

    // Determine the tables we need to consider
    $dbconn = xarDB::getConn();
    $tables =& xarDBGetTables();
    $modulestable = $tables['modules'];

    // Construct the order by clause and join it up into one string
    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) throw new BadParameterExceptions('orderBy');

        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.' . $orderField;
        if ($validOrderFields[$orderField] == 'mods') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);

    // Keep a record of the different conditions and their bindvars
    $whereClauses = array(); $bindvars = array();
    if (isset($filter['UserCapable'])) {
        $whereClauses[] = 'mods.user_capable = ?';
        $bindvars[] = $filter['UserCapable'];
    }
    if (isset($filter['AdminCapable'])) {
        $whereClauses[] = 'mods.admin_capable = ?';
        $bindvars[] = $filter['AdminCapable'];
    }
    if (isset($filter['Class'])) {
        $whereClauses[] = 'mods.class = ?';
        $bindvars[] = $filter['Class'];
    }
    if (isset($class)) {
        $whereClauses[] = 'mods.class = ?';
        $bindvars[] = $class;
    }
    if (isset($filter['Category'])) {
        $whereClauses[] = 'mods.category = ?';
        $bindvars[] = $filter['Category'];
    }
    if (isset($filter['State'])) {
        if ($filter['State'] != XARMOD_STATE_ANY) {
            if ($filter['State'] != XARMOD_STATE_INSTALLED) {
                $whereClauses[] = 'mods.state = ?';
                $bindvars[] = $filter['State'];
            } else {
                $whereClauses[] = 'mods.state != ? AND mods.state < ? AND mods.state != ?';
                $bindvars[] = XARMOD_STATE_UNINITIALISED;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'mods.state = ?';
        $bindvars[] = XARMOD_STATE_ACTIVE;
    }


    $whereClause = '';
    if (!empty($whereClauses)) {
        $whereClause = 'WHERE '. join(' AND ', $whereClauses);
    }
    $modList = array();

    $query = "SELECT mods.regid, mods.name, mods.directory,
                     mods.version, mods.id, mods.category, mods.state
                  FROM $modulestable mods $whereClause ORDER BY $orderByClause";

    // Got it
    $stmt = $dbconn->prepareStatement($query);
    $stmt->setLimit($numItems);
    $stmt->setOffset($startNum - 1);

    $result = $stmt->executeQuery($bindvars);

    while($result->next()) {
        list($modInfo['regid'],
             $modInfo['name'],
             $modInfo['directory'],
             $modInfo['version'],
             $modInfo['systemid'],
             $modInfo['category'],
             $modState) = $result->fields;

        if (xarVarIsCached('Mod.Infos', $modInfo['regid'])) {
            // Get infos from cache
            $modList[] = xarVarGetCached('Mod.Infos', $modInfo['regid']);
        } else {
            $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);
            $modInfo['displaydescription'] = xarModGetDisplayableDescription($modInfo['name']);
            // Shortcut for os prepared directory
            $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

            $modInfo['state'] = (int) $modState;

            xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
            if (isset($modFileInfo)) {
                $modInfo = array_merge($modFileInfo, $modInfo);
                xarVarSetCached('Mod.Infos', $modInfo['regid'], $modInfo);
                switch ($modInfo['state']) {
                case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
                    $modInfo['state'] = XARMOD_STATE_UNINITIALISED;
                    break;
                case XARMOD_STATE_MISSING_FROM_INACTIVE:
                    $modInfo['state'] = XARMOD_STATE_INACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_ACTIVE:
                    $modInfo['state'] = XARMOD_STATE_ACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_UPGRADED:
                    $modInfo['state'] = XARMOD_STATE_UPGRADED;
                    break;
                }
            }

            $modList[] = $modInfo;
        }
        $modInfo = array();
    }
    $result->close();
    return $modList;
}
?>
