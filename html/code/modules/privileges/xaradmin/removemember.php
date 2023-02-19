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
 * @return  array|string|void
 */
function privileges_admin_removemember()
{
    // Security
    if (!xarSecurity::check('EditPrivileges')) return; 
    
    // Check for authorization code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

// get input from any view of this page
   if (!xarVar::fetch('childid',  'int', $childid,  NULL, xarVar::NOT_REQUIRED)) {return;}
   if (!xarVar::fetch('parentid', 'int', $parentid, NULL, xarVar::NOT_REQUIRED)) {return;}

// call the API function
   if(!xarMod::apiFunc('privileges','admin','removemember', array('parentid' => $parentid, 'childid' => $childid))) {
   }

// redirect to the next page
    xarController::redirect(xarController::URL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('id'=>$childid)));
    return true;
}
