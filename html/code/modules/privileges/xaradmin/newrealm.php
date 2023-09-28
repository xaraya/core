<?php
/**
 * Create a new realm
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
 * addRealm - create a new realm
 * @return array<mixed>|string|void data for the template display
 */
function privileges_admin_newrealm()
{
    // Security
    if(!xarSecurity::check('AddPrivileges',0,'Realm')) return;

    $data = array();

    if (!xarVar::fetch('name',      'str:1:20', $name,      '',      xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('confirmed', 'bool', $confirmed, false, xarVar::NOT_REQUIRED)) return;

    if ($confirmed) {
        if (!xarSec::confirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        $dbconn = xarDB::getConn();
        $xartable =& xarDB::getTables();
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
        xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'));
    }

    $data['authid'] = xarSec::genAuthKey();
    return $data;
}
