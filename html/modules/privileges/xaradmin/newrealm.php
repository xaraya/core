<?php
/**
 * Create a new realm
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
 * addRealm - create a new realm
 * Takes no parameters
 */
function privileges_admin_newrealm()
{
    $data = array();

    if (!xarVarFetch('name',      'str:1:20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('AddPrivilege',0,'Realm')) return;

    if ($confirmed) {
        if (!xarSecConfirmAuthKey()) return;

        $xartable =& xarDBGetTables();
        $q = new xarQuery('SELECT',$xartable['security_realms'],'xar_name');
        $q->eq('xar_name', $name);
        if(!$q->run()) return;

        if ($q->getrows() > 0) {
            $msg = xarML('There is already a realm with the name #(1)', $name);
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA',
                           new DefaultUserException($msg));
            return;
        }

        $q = new xarQuery('INSERT',$xartable['security_realms']);
        $q->addfield('xar_name', $name);
        if(!$q->run()) return;
    }

    $data['authid'] = xarSecGenAuthKey();
    return $data;
}


?>