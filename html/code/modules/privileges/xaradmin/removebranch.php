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
 * removebranch - remove a privilege from a privilege
 *
 * Remove a privilege as a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @return  void
 */
function privileges_admin_removebranch()
{
    // Security
    if (!xarSecurityCheck('EditPrivileges')) return; 
    
// get input from any view of this page
   if (!xarVarFetch('childid',  'int', $childid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('parentid', 'int', $parentid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (empty($childid)) return xarResponse::notFound();
    if (empty($parentid)) return xarResponse::notFound();

// call the API function
   if(!xarMod::apiFunc('privileges','admin','removemember', array('parentid' => $parentid, 'childid' => $childid))) {
   }

// redirect to the next page
    xarController::redirect(xarModURL('privileges',
                             'admin',
                             'viewprivileges'));
    return true;
}
?>
