<?php
/**
 * File: $Id:
 * 
 * Remove a privilege from a privilege
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
 * removeMember - remove a privilege from a privilege
 *
 * Remove a privilege as a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  none
 * @throws  none
 * @todo    none
 */
function privileges_admin_removemember()
{


// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

// get input from any view of this page
   if (!xarVarFetch('childid',  'isset', $childid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('parentid', 'isset', $parentid, NULL, XARVAR_NOT_REQUIRED)) {return;}

// call the Roles class and get the parent and child objects
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($parentid);
    $member = $privs->getPrivilege($childid);

// assign the child to the parent and bail if an error was thrown
    $newpriv = $priv->removeMember($member);
    if (!$newpriv) {return;}

// set the session variable
    xarSessionSetVar('privileges_statusmsg', xarML('Removed from Privilege',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('pid'=>$childid)));
}

?>
