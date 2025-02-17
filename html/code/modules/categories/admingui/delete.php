<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use Xaraya\Modules\Categories\UserApi;
use BadParameterException;
use CategoryWorker;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin delete function
 * @extends MethodClass<AdminGui>
 */
class DeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete a category
     * This function also shows a count on the number of child categories of the current category
     * @return array|bool|string|void Returns display data array on success, null on failure
     * @throws \BadParameterException Thrown if given category was not found in API
     * @see AdminGui::delete()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $data = [];
        if (!xarVar::fetch('itemid', 'int:1:', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Security check
        if (!xarSecurity::check('ManageCategories', 1, 'category', "All:" . $data['itemid'])) {
            return;
        }

        // Root category cannot be deleted except by the site admin
        if (($data['itemid'] == 1) && (!xarUser::isSiteAdmin())) {
            return xarController::badRequest('no_privileges', $this->getContext());
        }

        // Check for confirmation
        if (empty($confirm)) {

            // Get category information
            $cat = $userapi->getcatinfo(['cid' => $data['itemid']]);

            if ($cat == false) {
                $msg = xarML('The category to be deleted does not exist', 'categories');
                throw new BadParameterException(null, $msg);
            }


            $data['cid'] = $data['itemid'];
            $data['name'] = $cat['name'];
            $data['authkey'] = xarSec::genAuthKey();

            $data['numcats'] = $userapi->countcats($cat);
            $data['numcats'] -= 1;
            $data['numitems'] = $userapi->countitems(['cids' => ['_' . $data['itemid']],
                'modid' => 0]);
            // Return output
            return $data;
        }


        // Confirm Auth Key
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $result = $worker->delete($data['itemid']);

        xarController::redirect(xarController::URL('categories', 'admin', 'view', []), null, $this->getContext());
        return true;
    }
}
