<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use Xaraya\Modules\Modules\AdminApi;
use xarController;
use xarMod;
use xarModUserVars;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List modules and current settings
     * @author Xaraya Development Team
     * @param array<string,mixed> $args several params from the associated form in template
     * @todo finish cleanup, styles, filters and sort orders
     * @return array|void data for the template display
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        if (!$adminapi->regenerate()) {
            return;
        }

        $coremods = ['base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories'];

        // Display phase
        $data = [];

        // Get the place at which we want to start disolaying
        xarVar::fetch('startnum', 'int:1:', $data['startnum'], 1, xarVar::NOT_REQUIRED);
        // Check for a state filter
        xarVar::fetch('state', 'int', $data['state'], null, xarVar::DONT_SET);
        // Check for a module type filter
        // 0=all, 1=core only, 2=non-core only
        xarVar::fetch('modtype', 'int:0:2', $data['modtype'], null, xarVar::DONT_SET);
        // Check for a sort: we can sort by name ASC or DESC
        xarVar::fetch('sort', 'pre:trim:upper:enum:ASC:DESC', $data['sort'], 'ASC', xarVar::NOT_REQUIRED);

        // Save the filters of this user
        if (!isset($data['state'])) {
            $data['state'] = xarModUserVars::get('modules', 'selfilter');
        }
        if (!isset($data['state'])) {
            $data['state'] = xarMod::STATE_ANY;
        }
        if (!isset($data['modtype'])) {
            $data['modtype'] = xarModUserVars::get('modules', 'hidecore');
        }
        if (!isset($data['modtype'])) {
            $data['modtype'] = 0;
        }
        $data['items_per_page'] = xarModVars::get('modules', 'items_per_page');
        $data['useicons'] = xarModVars::get('modules', 'use_module_icons');

        $itemargs = [
            'state' => $data['state'],
        ];

        if ($data['modtype'] == 1) {
            // core only
            $itemargs['name'] = $coremods;
        } elseif ($data['modtype'] == 2) {
            // non-core only
            $itemargs['include_core'] = false;
        }

        $data['total'] = $adminapi->countitems($itemargs);

        $itemargs += [
            'startnum' => $data['startnum'],
            'numitems' => $data['items_per_page'],
            'sort' => 'name ' . $data['sort'],
        ];

        $items = $adminapi->getitems($itemargs);

        $authid = xarSec::genAuthKey();

        foreach ($items as $key => $item) {
            $item['iscore'] = in_array($item['name'], $coremods);
            $item['info_url'] = xarController::URL(
                'modules',
                'admin',
                'modinfo',
                ['id' => $item['regid']]
            );
            $return_url = xarServer::getCurrentURL(['state' => $data['state'] != 0 ? 0 : null], false, $item['name']);
            $return_url = urlencode($return_url);
            switch ($item['state']) {
                case xarMod::STATE_UNINITIALISED: // 1
                    $item['init_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'install',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarMod::STATE_INACTIVE:  // 2
                    $item['activate_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'install',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    $item['remove_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'remove',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarMod::STATE_ACTIVE:  // 3
                    if (!$item['iscore']) {
                        $item['deactivate_url'] = xarController::URL(
                            'modules',
                            'admin',
                            'deactivate',
                            ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                        );
                    }
                    if (!empty($item['admin_capable'])) {
                        $item['admin_url'] = xarController::URL($item['name'], 'admin');
                    }
                    $item['hooks_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'modify',
                        ['id' => $item['regid']]
                    );
                    break;
                case xarMod::STATE_UPGRADED: // 5
                    $item['upgrade_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'upgrade',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarMod::STATE_MISSING_FROM_UNINITIALISED: // 4
                case xarMod::STATE_MISSING_FROM_INACTIVE: // 7
                case xarMod::STATE_MISSING_FROM_ACTIVE: // 8
                case xarMod::STATE_MISSING_FROM_UPGRADED: // 9
                    $item['remove_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'remove',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarMod::STATE_ERROR_UNINITIALISED: // 10
                case xarMod::STATE_ERROR_INACTIVE: // 11
                case xarMod::STATE_ERROR_ACTIVE: // 12
                case xarMod::STATE_ERROR_UPGRADED: // 13
                    $item['error_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'viewerror',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                default:
                    $item['remove_url'] = xarController::URL(
                        'modules',
                        'admin',
                        'remove',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
            }

            $items[$key] = $item;
        }

        $data['items'] = $items;

        $data['states'] = [
            xarMod::STATE_ANY =>
                ['id' => xarMod::STATE_ANY, 'name' => xarML('All')],
            xarMod::STATE_INSTALLED =>
                ['id' => xarMod::STATE_INSTALLED, 'name' => xarML('Installed')],
            xarMod::STATE_ACTIVE =>
                ['id' => xarMod::STATE_ACTIVE, 'name' => xarML('Active')],
            xarMod::STATE_UPGRADED =>
                ['id' => xarMod::STATE_UPGRADED, 'name' => xarML('Upgraded')],
            xarMod::STATE_INACTIVE =>
                ['id' => xarMod::STATE_INACTIVE, 'name' => xarML('Inactive')],
            xarMod::STATE_UNINITIALISED =>
                ['id' => xarMod::STATE_UNINITIALISED, 'name' => xarML('Not Installed')],
            xarMod::STATE_MISSING_FROM_ACTIVE =>
                ['id' => xarMod::STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')],
            xarMod::STATE_MISSING_FROM_UPGRADED =>
                ['id' => xarMod::STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')],
            xarMod::STATE_MISSING_FROM_INACTIVE =>
                ['id' => xarMod::STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')],
            xarMod::STATE_MISSING_FROM_UNINITIALISED =>
                ['id' => xarMod::STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Installed)')],
            xarMod::STATE_ERROR_ACTIVE =>
                ['id' => xarMod::STATE_ERROR_ACTIVE, 'name' => xarML('Error (Active)')],
            xarMod::STATE_ERROR_UPGRADED =>
                ['id' => xarMod::STATE_ERROR_UPGRADED, 'name' => xarML('Error (Upgraded)')],
            xarMod::STATE_ERROR_INACTIVE =>
                ['id' => xarMod::STATE_ERROR_INACTIVE, 'name' => xarML('Error (Inactive)')],
            xarMod::STATE_ERROR_UNINITIALISED =>
                ['id' => xarMod::STATE_ERROR_UNINITIALISED, 'name' => xarML('Error (Not Installed)')],
        ];

        $data['modtypes'] = [
            0 => ['id' => 0, 'name' => xarML('All')],
            1 => ['id' => 1, 'name' => xarML('Core')],
            2 => ['id' => 2, 'name' => xarML('Non-core')],
        ];

        // Remember filter selections for current user
        xarModUserVars::set('modules', 'selfilter', $data['state']);
        xarModUserVars::set('modules', 'hidecore', $data['modtype']);

        $count = count($items);
        if ($data['state'] == xarMod::STATE_ANY) {
            if ($data['modtype'] == 0) {
                $searched = xarML('Showing #(1) modules', $count);
            } else {
                $searched = xarML('Showing #(1) #(2) modules', $count, $data['modtypes'][$data['modtype']]['name']);
            }
        } else {
            if ($data['modtype'] == 0) {
                $searched = xarML('Showing #(1) modules in #(2) state', $count, $data['states'][$data['state']]['name']);
            } else {
                $searched = xarML('Showing #(1) #(2) modules in #(3) state', $count, $data['modtypes'][$data['modtype']]['name'], $data['states'][$data['state']]['name']);
            }
        }
        $data['searched'] = $searched;

        return $data;
    }
}
