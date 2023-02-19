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
 * @return boolean|string|void Returns true on success, string on security failure
 */
function categories_admin_create()
{
    // Confirm authorisation code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $data = [];
    //Checkbox work for submit buttons too
    if (!xarVar::fetch('return_url',  'isset',  $data['return_url'], NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('reassign', 'checkbox',  $reassign, false, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('repeat',   'int:1:100', $data['repeat'],   1,     xarVar::NOT_REQUIRED)) return;
    if ($reassign) {
        xarController::redirect(xarController::URL('categories','admin','new',array('repeat' => $data['repeat'])));
        return true;
    }

    sys::import('modules.dynamicdata.class.objects.master');
    for ($i=1;$i<=$data['repeat'];$i++) {
        $data['objects'][$i] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject'), 'fieldprefix' => $i));
        $isvalid = $data['objects'][$i]->checkInput();
    }

    if (!$isvalid) {
        $data['authid'] = xarSec::genAuthKey();
        return xarTpl::module('categories','admin','new',$data);
    }
    
    for ($i=1;$i<=$data['repeat'];$i++) {
        $data['objects'][$i]->createItem();
    }

    xarController::redirect(xarController::URL('categories','admin','view'));
//    xarController::redirect(xarController::URL('categories','admin','new',array('repeat' => $data['repeat'])));
    return true;
}
