<?php
/**
 * Get a list of modules that matches required criteria.
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
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
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function modules_adminapi_getlist($args)
{
    extract($args);
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods',
                                     'class' => 'mods', 'category' => 'mods');

    if (!isset($filter)) $filter = array();

    if (!is_array($filter)) {
        $msg = xarML('Parameter filter must be an array.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;
    if (!isset($orderBy)) $orderBy = 'name';

    // Determine the tables we need to consider
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    $modulestable = $tables['modules'];
    $module_statesTables = array($tables['system/module_states'], $tables['site/module_states']);

    // Construct the order by clause and join it up into one string
    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'orderBy');
            return;
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'mods') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);

    // Keep a record of the different conditions and their bindvars
    $whereClauses = array(); $bindvars = array();
    if (isset($filter['Mode'])) {
        $whereClauses[] = 'mods.xar_mode = ?';
        $bindvars[] = $filter['Mode'];
    }
    if (isset($filter['UserCapable'])) {
        $whereClauses[] = 'mods.xar_user_capable = ?';
        $bindvars[] = $filter['UserCapable'];
    }
    if (isset($filter['AdminCapable'])) {
        $whereClauses[] = 'mods.xar_admin_capable = ?';
        $bindvars[] = $filter['AdminCapable'];
    }
    if (isset($filter['Class'])) {
        $whereClauses[] = 'mods.xar_class = ?';
        $bindvars[] = $filter['Class'];
    }
    if (isset($class)) {
        $whereClauses[] = 'mods.xar_class = ?';
        $bindvars[] = $class;
    }
    if (isset($filter['Category'])) {
        $whereClauses[] = 'mods.xar_category = ?';
        $bindvars[] = $filter['Category'];
    }
    if (isset($filter['State'])) {
        if ($filter['State'] != XARMOD_STATE_ANY) {
            if ($filter['State'] != XARMOD_STATE_INSTALLED) {
                $whereClauses[] = 'states.xar_state = ?';
                $bindvars[] = $filter['State'];
            } else {
                $whereClauses[] = 'states.xar_state != ? AND states.xar_state < ? AND states.xar_state != ?';
                $bindvars[] = XARMOD_STATE_UNINITIALISED;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'states.xar_state = ?';
        $bindvars[] = XARMOD_STATE_ACTIVE;
    }

    // Here we do 2 SELECTs: one for SHARED moded modules and
    // one for PER_SITE moded modules
    // Maybe this could be done with a single query?
    $modList = array(); $mode = XARMOD_MODE_SHARED;
    for ($i = 0; $i < 2; $i++ ) {
        $module_statesTable = $module_statesTables[$i];

        $query = "SELECT mods.xar_regid, mods.xar_name, mods.xar_directory,
                         mods.xar_version, mods.xar_id, states.xar_state
                  FROM $modulestable mods
                  LEFT JOIN $module_statesTable states ON mods.xar_regid = states.xar_regid";

        // Add the first mode to the where clauses and join it into one string
        array_unshift($whereClauses, 'mods.xar_mode = ?');
        array_unshift($bindvars,$mode);
        $whereClause = join(' AND ', $whereClauses);
        $query .= " WHERE $whereClause ORDER BY $orderByClause";

        $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1,$bindvars);
        if (!$result) return;

        while(!$result->EOF) {
            list($modInfo['regid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['systemid'],
                 $modState) = $result->fields;

            if (xarVarIsCached('Mod.Infos', $modInfo['regid'])) {
                // Get infos from cache
                $modList[] = xarVarGetCached('Mod.Infos', $modInfo['regid']);
            } else {
                $modInfo['mode'] = (int) $mode;
                $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);
                $modInfo['displaydescription'] = xarModGetDisplayableDescription($modInfo['name']);
                // Shortcut for os prepared directory
                $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

                $modInfo['state'] = (int) $modState;

                xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

                $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
                if (isset($modFileInfo)) {
                    //     $modInfo = array_merge($modInfo, $modFileInfo);
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
            $result->MoveNext();
        }

        $result->Close();
        // Go over to the next mode
        $mode = XARMOD_MODE_PER_SITE;
        array_shift($whereClauses);
        array_shift($bindvars);
    }

    return $modList;
}
?>
