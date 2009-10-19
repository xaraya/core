<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
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
 * @param   childid, parentid
 * @return  boolean
 * @throws  none
 * @todo    none
 */
function privileges_adminapi_removemember($args)
{
    extract($args);
    //Do nothing if the params aren't there
    if(!isset($childid) || !isset($parentid)) return true;

// Get the parent and child objects
    $priv = Privileges_Privileges::getPrivilege($parentid);
    $member = Privileges_Privileges::getPrivilege($childid);

// assign the child to the parent and bail if an error was thrown
    if (!$priv->removeMember($member)) return;

// set the session variable
    xarSession::setVar('privileges_statusmsg', xarML('Removed from Privilege',
                    'privileges'));
    return true;
}

?>