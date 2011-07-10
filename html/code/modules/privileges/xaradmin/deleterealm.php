<?php
/**
 * Delete a realm
 *
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * deleteRealm - delete a realm
 * prompts for confirmation
 */
function privileges_admin_deleterealm()
{
    if (!xarVarFetch('id',          'isset', $id,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmed', 'isset', $confirmed, NULL, XARVAR_DONT_SET)) return;

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

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
    if(!xarSecurityCheck('ManagePrivileges',0,'Realm',$name)) return;

    if (empty($confirmed)) {
        $data['authid'] = xarSecGenAuthKey();
        $data['id'] = $id;
        $data['name'] = $name;
        return $data;
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $bindvars = array();
    $query = "DELETE FROM $tbl WHERE id = ?";
    $stmt = $dbconn->prepareStatement($query);
    $bindvars[] = $result_id;
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

// Hmm... what do we do about hooks?
//xarModCallHooks('item', 'delete', $id, '');

// redirect to the next page
    xarController::redirect(xarModURL('privileges', 'admin', 'viewrealms'));
    return true;
}

?>
