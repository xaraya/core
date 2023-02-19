<?php
/**
 * Delete a realm
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
 * deleteRealm - delete a realm
 * prompts for confirmation
 */
function privileges_admin_deleterealm()
{
    if (!xarVar::fetch('id',          'isset', $id,          NULL, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('confirmed', 'isset', $confirmed, NULL, xarVar::DONT_SET)) return;

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $bindvars = array();
    $tbl = $xartable['security_realms'];
    $query = "SELECT id, name FROM $tbl WHERE id = ?";
    $bindvars[] = $id;
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
    if(!$result) return;
    while($result->next())
    {
        list($result_id, $name) = $result->fields; 
    }

    // Security
    if (empty($name)) return xarResponse::NotFound();
    if(!xarSecurity::check('ManagePrivileges',0,'Realm',$name)) return;

    if (empty($confirmed)) {
        $data['authid'] = xarSec::genAuthKey();
        $data['id'] = $id;
        $data['name'] = $name;
        return $data;
    }

// Check for authorization code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $bindvars = array();
    $query = "DELETE FROM $tbl WHERE id = ?";
    $stmt = $dbconn->prepareStatement($query);
    $bindvars[] = $result_id;
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

// Hmm... what do we do about hooks?
//xarModHooks::call('item', 'delete', $id, '');

// redirect to the next page
    xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'));
    return true;
}