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
        $this->var()->find('itemid', $data['itemid'], 'int:1:', 0);
        $this->var()->find('confirm', $confirm, 'str:1:', '');

        // Security check
        if (!$this->sec()->check('ManageCategories', 1, 'category', "All:" . $data['itemid'])) {
            return;
        }

        // Root category cannot be deleted except by the site admin
        if (($data['itemid'] == 1) && (!$this->user()->isSiteAdmin())) {
            return $this->ctl()->badRequest('no_privileges');
        }

        // Check for confirmation
        if (empty($confirm)) {

            // Get category information
            $cat = $userapi->getcatinfo(['cid' => $data['itemid']]);

            if ($cat == false) {
                $msg = $this->ml('The category to be deleted does not exist', 'categories');
                throw new BadParameterException(null, $msg);
            }


            $data['cid'] = $data['itemid'];
            $data['name'] = $cat['name'];
            $data['authkey'] = $this->sec()->genAuthKey();

            $data['numcats'] = $userapi->countcats($cat);
            $data['numcats'] -= 1;
            $data['numitems'] = $userapi->countitems(['cids' => ['_' . $data['itemid']],
                'modid' => 0]);
            // Return output
            return $data;
        }


        // Confirm Auth Key
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $result = $worker->delete($data['itemid']);

        $this->ctl()->redirect($this->ctl()->getModuleURL('categories', 'admin', 'view', []));
        return true;
    }
}
