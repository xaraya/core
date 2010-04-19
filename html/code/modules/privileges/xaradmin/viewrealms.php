<?php
/**
 * View the defined realms
 *
 * @package core modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewRealms - view the defined realms
 * Takes no parameters
 */
function privileges_admin_viewrealms()
{
    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('AdminPrivileges',0,'Realm')) return;

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $rolesobjects = $xartable['security_realms'];
    $bindvars = array();
    $query = "SELECT id AS id, name AS name FROM $rolesobjects ";

    $query .= " ORDER BY name ";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
    if (!$result) return;
    while($result->next())
    {
        $data['realms'] = $result->fields;
    }
    return $data;
}


?>