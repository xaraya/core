<?php
/**
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
 * removeMember - remove a privilege from a privilege
 *
 * Remove a privilege as a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['childid']<br/>
 *        integer  $args['parentid']
 * @return  boolean
 */
function privileges_adminapi_removemember(Array $args=array())
{
    extract($args);
    //Do nothing if the params aren't there
    if(!isset($childid) || !isset($parentid)) return true;

// call the Privileges class and get the parent and child objects
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($parentid);
    $member = xarPrivileges::getPrivilege($childid);

// assign the child to the parent and bail if an error was thrown
    if (!$priv->removeMember($member)) return;

// set the session variable
    xarSession::setVar('privileges_statusmsg', xarML('Removed from Privilege',
                    'privileges'));
    return true;
}

?>
