<?php
/**
 * Modify an existing realm
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
 * modifyRealm - modify an existing realm
 * @param id of the realm to be modified
 * @return array data for the template display
 */
function privileges_admin_modifyrealm()
{
    // Security
    if(!xarSecurityCheck('EditPrivileges',0,'Realm')) return;

    if (!xarVarFetch('id',       'int', $id,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('name',      'str:1.20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    if (empty($confirmed)) {
        $bindvars = array();
        $tbl = $xartable['security_realms'];
        $query = "SELECT id, name FROM $tbl WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $id;
        $result = $stmt->executeQuery($bindvars);
        while($result->next()){
            list($result_id, $name) = $result->fields; 
        }
    } else {
        if (!xarVarFetch('newname',   'str:1.20',$newname, '',XARVAR_NOT_REQUIRED)) {return;}
        if (!xarSecConfirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        $bindvars = array();
        $name = '';
        $tbl = $xartable['security_realms'];
        $query = "SELECT name FROM $tbl WHERE name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $newname;
        $result = $stmt->executeQuery($bindvars);
        while($result->next()){
            list($name) = $result->fields; 
        }

        if ($name != '') throw new DuplicateException(array('realm',$newname));

        $bindvars = array();
        $tbl = $xartable['security_realms'];
        $query = "UPDATE $tbl SET name =? WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $newname;
        $bindvars[] = $id;
        $result = $stmt->executeQuery($bindvars);

        xarController::redirect(xarModURL('privileges', 'admin', 'viewrealms'));
    }

    $data['id'] = $id;
    $data['name'] = $name;
    $data['newname'] = '';
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>
