<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * create one or more new categories
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