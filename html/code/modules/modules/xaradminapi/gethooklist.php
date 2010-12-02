<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Obtain list of hooks (optionally for a particular module)
 *
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] optional module we're looking for
 * @return array of known hooks
 */
function modules_adminapi_gethooklist(Array $args=array())
{
    // Security Check
    // @CHECKME: is this info not useful to other modules?
    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($modName)) {
        $smodId = null;
        $modName = '';
    } else {
        $smodInfo = xarMod_GetBaseInfo($modName);
        $smodId = $smodInfo['systemid'];
    }

    $dbconn  = xarDB::getConn();
    $xartable = xarDB::getTables();

    // TODO: allow finer selection of hooks based on type etc., and
    //       filter out irrelevant ones (like module remove, search...)
    $bindvars = array();
    $query = "SELECT DISTINCT h.s_type, h.object, h.action, h.t_area, h.t_type,
                              h.t_func, h.t_file, h.s_module_id, h.t_module_id,
                              t.name
              FROM $xartable[hooks] h, $xartable[modules] t
              WHERE h.t_module_id = t.id ";

    if ($smodId != 0) {
        // Only get the hooks for $modName
        $query .= " AND ( h.s_module_id IS NULL OR  h.s_module_id = ? ) ";
        //   ORDER BY tmods.name,smods.name DESC";
        $bindvars[] = $smodId;
    } else {
        //$query .= " ORDER BY smods.name";
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);

    // @FIXME: this is seriously messed up, this should be two distinct functions
    // One to supply hook module info and the hooks they/it supplies 
    // (only module admin hooks needs this, and the event system can already return this info) 
    // One to supply the modules currently hooked to a module/itemtype 
    // (the hook system needs this, and the hook system can already get this info)
    // @TODO: use the event and hook system functions to build this crazy array for any
    // modules still using it (only crispbb and objecthooks ? afaics), 
    // and mark it deprecated so we can move away from this mess 
    // hooklist will hold the available hooks
    $hooklist = array();
    while($result->next()) {
        list($itemType, $object,$action,$area,$tmodType,$tmodFunc,$tmodFile,$smodId,$tmodId,$tmodName) = $result->fields;

        // Avoid single-space item types e.g. for mssql
        if (!empty($itemType)) $itemType = trim($itemType);

        if (!isset($hooklist[$tmodName]))
            $hooklist[$tmodName] = array();
        if (!isset($hooklist[$tmodName]["$object:$action:$area"]))
            $hooklist[$tmodName]["$object:$action:$area"] = array();
        // if the smodName has a value the hook is active
        if (!empty($smodId)) {
            if (!isset($hooklist[$tmodName]["$object:$action:$area"][$smodId]))
                $hooklist[$tmodName]["$object:$action:$area"][$smodId] = array();
            if (empty($itemType))
                $itemType = 0;
            $hooklist[$tmodName]["$object:$action:$area"][$smodId][$itemType] = 1;
        }
    }
    $result->close();

    return $hooklist;
}

?>
