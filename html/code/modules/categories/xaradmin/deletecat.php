<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Categories Module
 * @link http://xaraya.com/index.php/release/147.html
 * @author Categories module development team
 */
/**
 * Delete a category
 *
 * This function also shows a count on the number of child categories of the current category
 * @param id cid
 * @param str confirm OPTIONAL
 * @return bool
 */
function categories_admin_deletecat()
{
    if (!xarVarFetch('cid','int:1:',$cid)) return;
    if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;

    // Security check
    if(!xarSecurityCheck('ManageCategories',1,'category',"All:$cid")) return;

    // Check for confirmation
    if (empty($confirm)) {

        // Get category information
        $cat = xarMod::apiFunc('categories',
                             'user',
                             'getcatinfo',
                              array('cid' => $cid));

        if ($cat == false) {
            $msg = xarML('The category to be deleted does not exist', 'categories');
            throw new BadParameterException(null, $msg);
        }


        $data = Array('cid'=>$cid,'name'=>$cat['name']);
        $data['nolabel'] = xarML('No');
        $data['yeslabel'] = xarML('Yes');
        $data['authkey'] = xarSecGenAuthKey();

        $data['numcats'] = xarMod::apiFunc('categories','user','countcats',
                                         $cat);
        $data['numcats'] -= 1;
        $data['numitems'] = xarMod::apiFunc('categories','user','countitems',
                                          array('cids' => array('_'.$cid),
                                                'modid' => 0));
        // Return output
        return xarTplModule('categories','admin','delete',$data);
    }


    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Pass to API
    if (!xarMod::apiFunc('categories',
                       'admin',
                       'deletecat',
                       array('cid' => $cid))) return;

    xarController::redirect(xarModURL('categories','admin','viewcats', array()));

    return true;
}

?>