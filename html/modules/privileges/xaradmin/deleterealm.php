<?php
/**
 * Delete a realm
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
 * deleteRealm - delete a realm
 * prompts for confirmation
 */
function privileges_admin_deleterealm()
{
    if (!xarVarFetch('rid',          'isset', $rid,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmed', 'isset', $confirmed, NULL, XARVAR_DONT_SET)) return;

    $xartable =& xarDBGetTables();
    $q = new xarQuery('SELECT',$xartable['security_realms']);
    $q->addfields(array('xar_rid AS rid','xar_name AS name'));
    $q->eq('xar_rid', $rid);
    if(!$q->run()) return;
    $result = $q->row();
    $name = $result['name'];

// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Realm',$name)) return;

    if (empty($confirmed)) {
        $data['authid'] = xarSecGenAuthKey();
        $data['rid'] = $rid;
        $data['name'] = $name;
        return $data;
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    $q = new xarQuery('DELETE',$xartable['security_realms']);
    $q->eq('xar_rid', $result['rid']);
    if(!$q->run()) return;

// Hmm... what do we do about hooks?
//xarModCallHooks('item', 'delete', $pid, '');

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewrealms'));
}

?>
