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
 * Create one or more new categories
 * 
 * @return boolean|string Returns true on success, string on security failure
 */
function categories_admin_create()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    //Checkbox work for submit buttons too
    if (!xarVarFetch('return_url',  'isset',  $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('reassign', 'checkbox',  $reassign, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('repeat',   'int:1:100', $data['repeat'],   1,     XARVAR_NOT_REQUIRED)) return;
    if ($reassign) {
        xarController::redirect(xarModURL('categories','admin','new',array('repeat' => $data['repeat'])));
        return true;
    }

    sys::import('modules.dynamicdata.class.objects.master');
    for ($i=1;$i<=$data['repeat'];$i++) {
        $data['objects'][$i] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject'), 'fieldprefix' => $i));
        $isvalid = $data['objects'][$i]->checkInput();
    }

    if (!$isvalid) {
        $data['authid'] = xarSecGenAuthKey();
        return xarTplModule('categories','admin','new',$data);
    }
    
    for ($i=1;$i<=$data['repeat'];$i++) {
        $data['objects'][$i]->createItem();
    }

    xarController::redirect(xarModURL('categories','admin','view'));
//    xarController::redirect(xarModURL('categories','admin','new',array('repeat' => $data['repeat'])));
    return true;
}
?>