<?php
/**
 * Delete a realm
 *
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
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

    $xartable = xarDB::getTables();
    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery('SELECT',$xartable['security_realms']);
    $q->addfields(array('id','name'));
    $q->eq('id', $id);
    if(!$q->run()) return;
    $result = $q->row();
    $name = $result['name'];

// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Realm',$name)) return;

    if (empty($confirmed)) {
        $data['authid'] = xarSecGenAuthKey();
        $data['id'] = $id;
        $data['name'] = $name;
        return $data;
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    $q = new xarQuery('DELETE',$xartable['security_realms']);
    $q->eq('id', $result['id']);
    if(!$q->run()) return;

// Hmm... what do we do about hooks?
//xarModCallHooks('item', 'delete', $id, '');

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewrealms'));
}

?>
