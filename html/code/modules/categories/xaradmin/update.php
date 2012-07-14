<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * update item from categories_admin_modify
 */
function categories_admin_update()
{
    //Checkbox work for submit buttons too
    if (!xarVarFetch('itemtype', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'int', $data['itemid'], 0, XARVAR_NOT_REQUIRED)) return;

    // Support old cids for now
    if (!xarVarFetch('cid','int::', $cid, NULL, XARVAR_DONT_SET)) {return;}
    $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    //Reverses the order of cids with the 'last children' option:
    //Look at bug #997

    sys::import('modules.dynamicdata.class.objects.master');
    $data['object'] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
    $isvalid = $data['object']->checkInput();

    if (!$isvalid) {
        $data['authid'] = xarSecGenAuthKey();
        return xarTplModule('categories','admin','modfiy',$data);
    }

    $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));
    xarController::redirect(xarModUrl('categories','admin','view'));
    return true;
}
?>