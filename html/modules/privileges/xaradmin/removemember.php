<?php
/**
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
   if (!xarVarFetch('childid',  'int', $childid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('parentid', 'int', $parentid, NULL, XARVAR_NOT_REQUIRED)) {return;}

// call the API function
   if(!xarModAPIFunc('privileges','admin','removemember', array('parentid' => $parentid, 'childid' => $childid))) {
   }

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('pid'=>$childid)));
}
?>
