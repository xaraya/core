<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Update item from categories_admin_modify
 * 
 * @param void N/A
 * @return boolean|null Returns true on success, null on failure
 */
function categories_admin_update()
{
    //Checkbox work for submit buttons too
    if (!xarVar::fetch('itemtype', 'int', $itemtype, 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED)) return;

    // Support old cids for now
    if (!xarVar::fetch('cid','int::', $cid, NULL, xarVar::DONT_SET)) {return;}
    $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

    // Confirm authorisation code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Root category cannot be modified except by the site admin
    if (($cid == 1) && (xarUser::getVar('id') != xarModVars::get('roles', 'admin')))
        return xarTpl::module('privileges','user','errors', array('layout' => 'no_privileges'));

    //Reverses the order of cids with the 'last children' option:
    //Look at bug #997

    sys::import('modules.dynamicdata.class.objects.master');
    $data['object'] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
    $isvalid = $data['object']->checkInput();

    if (!$isvalid) {
        $data['authid'] = xarSec::genAuthKey();
        return xarTpl::module('categories','admin','modfiy',$data);
    }

    $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));
    xarController::redirect(xarModUrl('categories','admin','view'));
    return true;
}
?>