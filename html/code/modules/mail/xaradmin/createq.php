<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

function mail_admin_createqArray(Array $args=array())
{
    // Are we allowed to be here?
    if (!xarSecurityCheck('AdminMail')) return;
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // What do we need to do
    if(!xarVarFetch('name','str:1:12',$qName)) return;

    // Do we have the master ?
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarController::redirect(xarModUrl('mail','admin','view'));
        return true;
    }

    // Seems ok, call the create function
    $qData = xarMod::apiFunc('mail','admin','createq',array('name' => $qName));
    if(!$qData) return; // exception
    
    // Show the status screen again, 
    xarController::redirect(xarModUrl('mail','admin','qstatus'));
    return true;
}
?>