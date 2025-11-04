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
use ixarMod;
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
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        if (!$adminapi->regenerate()) {
            return;
        }

        $coremods = ['base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories'];

        // Display phase
        $data = [];

        // Get the place at which we want to start disolaying
        $this->var()->find('startnum', $data['startnum'], 'int:1:', 1);
        // Check for a state filter
        $this->var()->check('state', $data['state'], 'int', null);
        // Check for a module type filter
        // 0=all, 1=core only, 2=non-core only
        $this->var()->check('modtype', $data['modtype'], 'int:0:2', null);
        // Check for a sort: we can sort by name ASC or DESC
        $this->var()->find('sort', $data['sort'], 'pre:trim:upper:enum:ASC:DESC', 'ASC');

        // Save the filters of this user
        if (!isset($data['state'])) {
            $data['state'] = $this->mod()->getUserVar('selfilter');
        }
        if (!isset($data['state'])) {
            $data['state'] = ixarMod::STATE_ANY;
        }
        if (!isset($data['modtype'])) {
            $data['modtype'] = $this->mod()->getUserVar('hidecore');
        }
        if (!isset($data['modtype'])) {
            $data['modtype'] = 0;
        }
        $data['items_per_page'] = $this->mod()->getVar('items_per_page');
        $data['useicons'] = $this->mod()->getVar('use_module_icons');

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

        $authid = $this->sec()->genAuthKey();

        foreach ($items as $key => $item) {
            $item['iscore'] = in_array($item['name'], $coremods);
            $item['info_url'] = $this->ctl()->getModuleURL(
                'modules',
                'admin',
                'modinfo',
                ['id' => $item['regid']]
            );
            $return_url = $this->ctl()->getCurrentURL(['state' => $data['state'] != 0 ? 0 : null], false) . '#' . $item['name'];
            $return_url = urlencode($return_url);
            switch ($item['state']) {
                case ixarMod::STATE_UNINITIALISED: // 1
                    $item['init_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'install',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case ixarMod::STATE_INACTIVE:  // 2
                    $item['activate_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'install',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    $item['remove_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'remove',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case ixarMod::STATE_ACTIVE:  // 3
                    if (!$item['iscore']) {
                        $item['deactivate_url'] = $this->ctl()->getModuleURL(
                            'modules',
                            'admin',
                            'deactivate',
                            ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                        );
                    }
                    if (!empty($item['admin_capable'])) {
                        $item['admin_url'] = $this->ctl()->getModuleURL($item['name'], 'admin');
                    }
                    $item['hooks_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'modify',
                        ['id' => $item['regid']]
                    );
                    break;
                case ixarMod::STATE_UPGRADED: // 5
                    $item['upgrade_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'upgrade',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case ixarMod::STATE_MISSING_FROM_UNINITIALISED: // 4
                case ixarMod::STATE_MISSING_FROM_INACTIVE: // 7
                case ixarMod::STATE_MISSING_FROM_ACTIVE: // 8
                case ixarMod::STATE_MISSING_FROM_UPGRADED: // 9
                    $item['remove_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'remove',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case ixarMod::STATE_ERROR_UNINITIALISED: // 10
                case ixarMod::STATE_ERROR_INACTIVE: // 11
                case ixarMod::STATE_ERROR_ACTIVE: // 12
                case ixarMod::STATE_ERROR_UPGRADED: // 13
                    $item['error_url'] = $this->ctl()->getModuleURL(
                        'modules',
                        'admin',
                        'viewerror',
                        ['id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                default:
                    $item['remove_url'] = $this->ctl()->getModuleURL(
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
            ixarMod::STATE_ANY
                => ['id' => ixarMod::STATE_ANY, 'name' => $this->ml('All')],
            ixarMod::STATE_INSTALLED
                => ['id' => ixarMod::STATE_INSTALLED, 'name' => $this->ml('Installed')],
            ixarMod::STATE_ACTIVE
                => ['id' => ixarMod::STATE_ACTIVE, 'name' => $this->ml('Active')],
            ixarMod::STATE_UPGRADED
                => ['id' => ixarMod::STATE_UPGRADED, 'name' => $this->ml('Upgraded')],
            ixarMod::STATE_INACTIVE
                => ['id' => ixarMod::STATE_INACTIVE, 'name' => $this->ml('Inactive')],
            ixarMod::STATE_UNINITIALISED
                => ['id' => ixarMod::STATE_UNINITIALISED, 'name' => $this->ml('Not Installed')],
            ixarMod::STATE_MISSING_FROM_ACTIVE
                => ['id' => ixarMod::STATE_MISSING_FROM_ACTIVE, 'name' => $this->ml('Missing (Active)')],
            ixarMod::STATE_MISSING_FROM_UPGRADED
                => ['id' => ixarMod::STATE_MISSING_FROM_UPGRADED, 'name' => $this->ml('Missing (Upgraded)')],
            ixarMod::STATE_MISSING_FROM_INACTIVE
                => ['id' => ixarMod::STATE_MISSING_FROM_INACTIVE, 'name' => $this->ml('Missing (Inactive)')],
            ixarMod::STATE_MISSING_FROM_UNINITIALISED
                => ['id' => ixarMod::STATE_MISSING_FROM_UNINITIALISED, 'name' => $this->ml('Missing (Not Installed)')],
            ixarMod::STATE_ERROR_ACTIVE
                => ['id' => ixarMod::STATE_ERROR_ACTIVE, 'name' => $this->ml('Error (Active)')],
            ixarMod::STATE_ERROR_UPGRADED
                => ['id' => ixarMod::STATE_ERROR_UPGRADED, 'name' => $this->ml('Error (Upgraded)')],
            ixarMod::STATE_ERROR_INACTIVE
                => ['id' => ixarMod::STATE_ERROR_INACTIVE, 'name' => $this->ml('Error (Inactive)')],
            ixarMod::STATE_ERROR_UNINITIALISED
                => ['id' => ixarMod::STATE_ERROR_UNINITIALISED, 'name' => $this->ml('Error (Not Installed)')],
        ];

        $data['modtypes'] = [
            0 => ['id' => 0, 'name' => $this->ml('All')],
            1 => ['id' => 1, 'name' => $this->ml('Core')],
            2 => ['id' => 2, 'name' => $this->ml('Non-core')],
        ];

        // Remember filter selections for current user
        $this->mod()->setUserVar('selfilter', $data['state']);
        $this->mod()->setUserVar('hidecore', $data['modtype']);

        $count = count($items);
        if ($data['state'] == ixarMod::STATE_ANY) {
            if ($data['modtype'] == 0) {
                $searched = $this->ml('Showing #(1) modules', $count);
            } else {
                $searched = $this->ml('Showing #(1) #(2) modules', $count, $data['modtypes'][$data['modtype']]['name']);
            }
        } else {
            if ($data['modtype'] == 0) {
                $searched = $this->ml('Showing #(1) modules in #(2) state', $count, $data['states'][$data['state']]['name']);
            } else {
                $searched = $this->ml('Showing #(1) #(2) modules in #(3) state', $count, $data['modtypes'][$data['modtype']]['name'], $data['states'][$data['state']]['name']);
            }
        }
        $data['searched'] = $searched;

        return $data;
    }
}
