<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
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
 * @return  void
 */
function privileges_admin_removemember()
{
    // Security
    if (!xarSecurityCheck('EditPrivileges')) return; 
    
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

// get input from any view of this page
   if (!xarVarFetch('childid',  'int', $childid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('parentid', 'int', $parentid, NULL, XARVAR_NOT_REQUIRED)) {return;}

// call the API function
   if(!xarMod::apiFunc('privileges','admin','removemember', array('parentid' => $parentid, 'childid' => $childid))) {
   }

// redirect to the next page
    xarController::redirect(xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('id'=>$childid)));
    return true;
}
?>
