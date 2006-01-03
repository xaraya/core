<?php
/**
 * Modify an existing realm
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * modifyRealm - modify an existing realm
 * @param rid the id of the realm to be modified
 */
function privileges_admin_modifyrealm()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Realm')) return;

    if (!xarVarFetch('rid',      'int', $rid,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('name',      'str:1.20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    $xartable =& xarDBGetTables();

    if (empty($confirmed)) {
        $q = new xarQuery('SELECT',$xartable['security_realms']);
        $q->addfields(array('xar_rid AS rid','xar_name AS name'));
        $q->eq('xar_rid', $rid);
        if(!$q->run()) return;
        $result = $q->row();
        $name = $result['name'];
    } else {
        if (!xarSecConfirmAuthKey()) return;

        $q = new xarQuery('SELECT',$xartable['security_realms'],'xar_name');
        $q->eq('xar_name', $name);
        if(!$q->run()) return;

        if ($q->getrows() > 1) {
            $msg = xarML('There is already a realm with the name #(1)', $name);
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA',
                           new DefaultUserException($msg));
            return;
        }

        $q = new xarQuery('UPDATE',$xartable['security_realms']);
        $q->addfield('xar_name', $name);
        if(!$q->run()) return;
    }

    $data['rid'] = $rid;
    $data['name'] = $name;
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}


?>