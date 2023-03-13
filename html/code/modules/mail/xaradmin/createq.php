<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

function mail_admin_createqArray(Array $args=array())
{
    // Security
    if (!xarSecurity::check('AdminMail')) return;
    
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // What do we need to do
    if(!xarVar::fetch('name','str:1:12',$qName)) return;

    // Do we have the master ?
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarController::redirect(xarController::URL('mail','admin','view'));
        return true;
    }

    // Seems ok, call the create function
    $qData = xarMod::apiFunc('mail','admin','createq',array('name' => $qName));
    if(!$qData) return; // exception
    
    // Show the status screen again, 
    xarController::redirect(xarController::URL('mail','admin','qstatus'));
    return true;
}
