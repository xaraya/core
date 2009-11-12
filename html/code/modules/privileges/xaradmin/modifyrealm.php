<?php
/**
 * Modify an existing realm
 *
 * @package core modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * modifyRealm - modify an existing realm
 * @param id of the realm to be modified
 */
function privileges_admin_modifyrealm()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Realm')) return;

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
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
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