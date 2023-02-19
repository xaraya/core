<?php
/**
 * Modify an existing realm
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
 * modifyRealm - modify an existing realm
 * @param int id of the realm to be modified
 * @return array|string|void data for the template display
 */
function privileges_admin_modifyrealm()
{
    // Security
    if(!xarSecurity::check('EditPrivileges',0,'Realm')) return;

    if (!xarVar::fetch('id',       'int', $id,      '',      xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('confirmed', 'bool', $confirmed, false, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('name',      'str:1.20', $name,      '',      xarVar::NOT_REQUIRED)) {return;}
    
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

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
        if (!xarVar::fetch('newname',   'str:1.20',$newname, '',xarVar::NOT_REQUIRED)) {return;}
        if (!xarSec::confirmAuthKey()) {
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

        xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'));
    }

    $data['id'] = $id;
    $data['name'] = $name;
    $data['newname'] = '';
    $data['authid'] = xarSec::genAuthKey();
    return $data;
}
?>
