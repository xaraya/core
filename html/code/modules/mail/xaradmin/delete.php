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

function mail_admin_delete($args = array())
{
    // Are we legitimally here?
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Security
    if (!xarSecurity::check('ManageMail')) return; 
    
    // Required parameters
    if(!xarVar::fetch('itemid','int:1:',$itemid, 0, xarVar::NOT_REQUIRED)) return;
    if(!xarVar::fetch('objectid','int:1:',$objectid, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($itemid) || empty($objectid)) return xarResponse::notFound();

    $qdefObject = xarMod::apiFunc('dynamicdata','user','getobject',array('objectid' => $objectid));
    if(!$qdefObject) return;

    $result = $qdefObject->deleteItem(array('itemid' => $itemid));
    if(!$result) return;

    return xarController::redirect(xarController::URL('mail','admin','view'));
}
