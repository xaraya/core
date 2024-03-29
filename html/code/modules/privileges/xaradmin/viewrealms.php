<?php
/**
 * View the defined realms
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewRealms - view the defined realms
 * @return array<mixed>|string|void data for the template display
 */
function privileges_admin_viewrealms()
{
    // Security
    if(!xarSecurity::check('AdminPrivileges',0,'Realm')) return;

    $data = array();

    if (!xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED)) return;

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $rolesobjects = $xartable['security_realms'];
    $bindvars = array();
    $query = "SELECT id AS id, name AS name FROM $rolesobjects ";

    $query .= " ORDER BY name ";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
    if (!$result) return;
    while($result->next())
    {
        $data['realms'] = $result->fields;
    }
    return $data;
}
