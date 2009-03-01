<?php
/**
 * DeletePrivilege - delete a privilege
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
 * deletePrivilege - delete a privilege
 * prompts for confirmation
 */
function privileges_admin_deleteprivilege()
{
    if (!xarVarFetch('id',          'isset', $id,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) return;

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

//Call the Privileges class and get the privilege to be deleted
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getprivilege($id);
    $name = $priv->getName();

// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',$name)) return;

    if (empty($confirmation)) {

//Get the array of parents of this privilege
        $parents = array();
        foreach ($priv->getParents() as $parent) {
            $parents[] = array('parentid'=>$parent->getID(),
                                        'parentname'=>$parent->getName());
        }
        //Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['id'] = $id;
        $data['pname'] = $name;
        $data['parents'] = $parents;
        return $data;

    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

//Try to remove the privilege and bail if an error was thrown
    if (!$priv->remove()) return;

    xarModCallHooks('item', 'delete', $id, '');

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Removed',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));
}

?>
