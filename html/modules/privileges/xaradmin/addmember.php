<?php

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

// call the Privileges class and get the parent and child objects
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);
    $member = $privs->getPrivilege($privid);

// assign the child to the parent and bail if an error was thrown
// we bail if the child is already a member of the *parent*
// if the child was a member of an ancestor further up that would be OK.
    $children = $priv->getChildren();
    foreach ($children as $child) if ($child->getID() == $member->getID()) $found = true;
    if (!isset($found)) if (!$priv->addMember($member)) {return;}

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
