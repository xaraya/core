<?php
/**
 * AddMember
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
 * addMember - assign a privilege as a member of another privilege
 *
 * Make a privilege a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @return  none
 */
function privileges_admin_addmember()
{
    // Security
    if (!xarSecurityCheck('AddPrivileges')) return; 
    
// Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if(!xarVarFetch('ppid',   'isset', $id   , NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('privid', 'isset', $privid, NULL, XARVAR_DONT_SET)) {return;}

    if (empty($id) || empty($privid)) {
        xarController::redirect(xarModURL('privileges',
                                      'admin',
                                      'modifyprivilege',
                                      array('id'=>$id)));
        return true;
    }

// call the Privileges class and get the parent and child objects
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);
    $member = xarPrivileges::getPrivilege($privid);

// we bail if there is a loop: the child is already an ancestor of the parent
    $found = false;
    $descendants = $member->getDescendants();
    foreach ($descendants as $descendant) if ($descendant->getID() == $priv->getID()) $found = true;
    if ($found) {
        throw new DuplicateException(null,'The privilege you are trying to assign to is already a component of the one you are assigning.');
    }

// assign the child to the parent and bail if an error was thrown
// we bail if the child is already a member of the *parent*
// if the child was a member of an ancestor further up that would be OK.
    $found = false;
    $children = $priv->getChildren();
    foreach ($children as $child) if ($child->getID() == $member->getID()) $found = true;
    if (!$found) if (!$priv->addMember($member)) {return;}

// set the session variable
    xarSession::setVar('privileges_statusmsg', xarML('Added to Privilege',
                    'privileges'));
// redirect to the next page
    xarController::redirect(xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('id'=>$id)));
    return true;
}


?>
