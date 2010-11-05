<?php
/**
 * Create a new realm
 *
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * addRealm - create a new realm
 * Takes no parameters
 */
function privileges_admin_newrealm()
{
    $data = array();

    if (!xarVarFetch('name',      'str:1:20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('AddPrivileges',0,'Realm')) return;

    if ($confirmed) {
        if (!xarSecConfirmAuthKey()) {
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $bindvars = array();
        $tbl = $xartable['security_realms'];
        $query = "SELECT name FROM $tbl WHERE name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $name;
        $result = $stmt->executeQuery($bindvars);
        while($result->next()){
            list($name) = $result->fields; 
        }

        if ($name != '') {
            throw new DuplicateException(array('realm',$name));
        }

        $bindvars = array();
        $tbl = $xartable['security_realms'];
        $query = "INSERT into $tbl (name) values(?)";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $name;
        $result = $stmt->executeQuery($bindvars);

        //Redirect to view page
        xarController::redirect(xarModURL('privileges', 'admin', 'viewrealms'));
    }

    $data['authid'] = xarSecGenAuthKey();
    return $data;
}


?>
