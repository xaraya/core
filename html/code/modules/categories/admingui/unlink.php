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
use Xaraya\Modules\Categories\AdminApi;
use Exception;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin unlink function
 * @extends MethodClass<AdminGui>
 */
class UnlinkMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete category links of module items.
     * @return bool|array|string|void Returns true on success, null on failure.
     * @see AdminGui::unlink()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security Check
        if (!xarSecurity::check('AdminCategories')) {
            return;
        }

        if (!xarVar::fetch('modid', 'isset', $modid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('catid', 'isset', $catid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Check for confirmation.
        if (empty($confirm)) {
            $data = [];
            $data['modid'] = $modid;
            $data['itemtype'] = $itemtype;
            $data['itemid'] = $itemid;

            $what = '';
            if (!empty($modid)) {
                $modinfo = xarMod::getInfo($modid);
                if (empty($itemtype)) {
                    $data['modname'] = ucwords($modinfo['displayname']);
                } else {
                    // Get the list of all item types for this module (if any)
                    try {
                        $mytypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                    } catch (Exception $e) {
                        $mytypes = [];
                    }
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                    } else {
                        $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                    }
                }
            }
            $data['confirmbutton'] = xarML('Confirm');
            // Generate a one-time authorisation code for this operation
            $data['authid'] = xarSec::genAuthKey();
            // Return the template variables defined in this function
            return $data;
        }

        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }
        // unlink API does not support deleting all category links for all modules
        if (!empty($modid)) {
            $modinfo = xarMod::getInfo($modid);
            if (!$adminapi->unlink(['modid' => $modid,
                'itemtype' => $itemtype,
                'iid' => $itemid,
                'confirm' => $confirm])) {
                return;
            }
            // TODO: support deleting all links for a category too (cfr. checklinks)
        }
        xarController::redirect(xarController::URL('categories', 'admin', 'stats'), null, $this->getContext());
        return true;
    }
}
