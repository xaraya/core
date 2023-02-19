<?php
/**
 * DeletePrivilege - delete a privilege
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
 * deletePrivilege - delete a privilege
 * prompts for confirmation
 */
function privileges_admin_deleteprivilege()
{
    if (!xarVar::fetch('id',          'isset', $id,          NULL, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('confirmation', 'isset', $confirmation, NULL, xarVar::DONT_SET)) return;

// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

//Call the Privileges class and get the privilege to be deleted
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getprivilege($id);
    if (empty($priv)) return xarResponse::NotFound();
    $name = $priv->getName();

    // Security
    if(!xarSecurity::check('ManagePrivileges',0,'Privileges',$name)) return;

    if (empty($confirmation)) {

//Get the array of parents of this privilege
        $parents = array();
        foreach ($priv->getParents() as $parent) {
            $parents[] = array('parentid'=>$parent->getID(),
                                        'parentname'=>$parent->getName());
        }
        //Load Template
        $data['authid'] = xarSec::genAuthKey();
        $data['id'] = $id;
        $data['pname'] = $name;
        $data['parents'] = $parents;
        return $data;

    }

// Check for authorization code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

//Try to remove the privilege and bail if an error was thrown
    if (!$priv->remove()) return;

    xarModHooks::call('item', 'delete', $id, '');

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Removed',
                    'privileges'));

// redirect to the next page
    xarController::redirect(xarController::URL('privileges', 'admin', 'viewprivileges'));
    return true;
}
