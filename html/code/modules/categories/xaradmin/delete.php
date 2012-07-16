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
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/**
 * Delete a category
 *
 * This function also shows a count on the number of child categories of the current category
 * @param id cid
 * @param str confirm OPTIONAL
 * @return bool
 */
function categories_admin_delete()
{
    if (!xarVarFetch('itemid','int:1:',$data['itemid'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;

    // Security check
    if(!xarSecurityCheck('ManageCategories',1,'category',"All:" . $data['itemid'])) return;

    // Check for confirmation
    if (empty($confirm)) {

        // Get category information
        $cat = xarMod::apiFunc('categories',
                             'user',
                             'getcatinfo',
                              array('cid' => $data['itemid']));

        if ($cat == false) {
            $msg = xarML('The category to be deleted does not exist', 'categories');
            throw new BadParameterException(null, $msg);
        }


        $data['cid'] = $data['itemid'];
        $data['name'] = $cat['name'];
        $data['authkey'] = xarSecGenAuthKey();

        $data['numcats'] = xarMod::apiFunc('categories','user','countcats',
                                         $cat);
        $data['numcats'] -= 1;
        $data['numitems'] = xarMod::apiFunc('categories','user','countitems',
                                          array('cids' => array('_'.$data['itemid']),
                                                'modid' => 0));
        // Return output
        return $data;
    }


    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    sys::import('modules.dynamicdata.class.objects.master');
    $data['objects'] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
    $data['objects']->deleteItem(array('itemid' => $data['itemid']));

    xarController::redirect(xarModURL('categories','admin','view', array()));

    return true;
}

?>