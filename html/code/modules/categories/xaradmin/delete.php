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
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Delete a category
 *
 * This function also shows a count on the number of child categories of the current category
 * 
 * @return array|bool|string|void Returns display data array on success, null on failure
 * @throws BadParameterException Thrown if given category was not found in API
 */
function categories_admin_delete()
{
    $data = [];
    if (!xarVar::fetch('itemid','int:1:',$data['itemid'], 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('confirm','str:1:',$confirm,'',xarVar::NOT_REQUIRED)) return;

    // Security check
    if(!xarSecurity::check('ManageCategories',1,'category',"All:" . $data['itemid'])) return;

    // Root category cannot be deleted except by the site admin
    if (($data['itemid'] == 1) && (xarUser::getVar('id') != xarModVars::get('roles', 'admin')))
        return xarTpl::module('privileges','user','errors', array('layout' => 'no_privileges'));

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
        $data['authkey'] = xarSec::genAuthKey();

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
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    $result = $worker->delete($data['itemid']);

    xarController::redirect(xarController::URL('categories','admin','view', array()));
    return true;
}
