<?php
/**
 * AddMember
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
 * addMember - assign a privilege as a member of another privilege
 *
 * Make a privilege a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  none
 * @throws  none
 * @todo    none
 */
function privileges_admin_addmember()
{

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    if(!xarVarFetch('ppid',   'isset', $pid   , NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('privid', 'isset', $privid, NULL, XARVAR_DONT_SET)) {return;}

    if (empty($pid) || empty($privid)) {
        xarResponseRedirect(xarModURL('privileges',
                                      'admin',
                                      'modifyprivilege',
                                      array('pid'=>$pid)));
        return true;
    }

// call the Privileges class and get the parent and child objects
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);
    $member = $privs->getPrivilege($privid);

// we bail if there is a loop: the child is already an ancestor of the parent
    $found = false;
    $descendants = $member->getDescendants();
    foreach ($descendants as $descendant) if ($descendant->getID() == $priv->getID()) $found = true;
    if ($found) {
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException("The privilege you are trying to assign to is already a component of the one you are assigning."));
        return;
    }

// assign the child to the parent and bail if an error was thrown
// we bail if the child is already a member of the *parent*
// if the child was a member of an ancestor further up that would be OK.
    $found = false;
    $children = $priv->getChildren();
    foreach ($children as $child) if ($child->getID() == $member->getID()) $found = true;
    if (!$found) if (!$priv->addMember($member)) {return;}

// set the session variable
    xarSessionSetVar('privileges_statusmsg', xarML('Added to Privilege',
                    'privileges'));
// redirect to the next page
    xarResponseRedirect(xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('pid'=>$pid)));
}


?>
