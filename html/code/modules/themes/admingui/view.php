<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use Xaraya\Modules\Themes\AdminApi;
use xarController;
use xarCoreCache;
use xarMod;
use xarModUserVars;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarTheme;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List themes and current settings
     * @author Marty Vance
     * @author Chris Powis <crisp@xaraya.com>
     * @return array|string|void data for the template display
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // lets regenerate the list on each reload, for now
        if (!$adminapi->regenerate()) {
            return;
        }

        $this->var()->check(
            'phase',
            $phase,
            'pre:trim:lower:enum:update',
            null
        );

        // update default themes
        if ($phase == 'update') {
            if (!xarSec::confirmAuthKey()) {
                return xarController::badRequest('bad_author', $this->getContext());
            }
            $old_user_theme = xarModVars::get('themes', 'default_theme');
            $old_admin_theme = xarModVars::get('themes', 'admin_theme');
            $this->var()->check(
                'user_theme',
                $new_user_theme,
                'pre:trim:lower:str:1:',
                $old_user_theme
            );
            $this->var()->check(
                'admin_theme',
                $new_admin_theme,
                'pre:trim:lower:str:1:',
                $old_admin_theme
            );
            if ($new_user_theme != $old_user_theme) {
                $themeid = xarTheme::getIDFromName($new_user_theme);
                if ($themeid) {
                    $info = xarTheme::getInfo($themeid);
                    if ($info['class'] != 2) {
                        $new_user_theme = $old_user_theme;
                    } else {
                        if (xarCoreCache::isCached('Mod.Variables.themes', 'default_theme')) {
                            xarCoreCache::delCached('Mod.Variables.themes', 'default_theme');
                        }
                        if (!$adminapi->install(['regid' => $themeid])) {
                            $new_user_theme = $old_user_theme;
                        }
                    }
                } else {
                    $new_user_theme = $old_user_theme;
                }
                xarModVars::set('themes', 'default_theme', $new_user_theme);
            }
            if ($new_admin_theme != $old_admin_theme) {
                $themeid = xarTheme::getIDFromName($new_admin_theme);
                if ($themeid) {
                    $info = xarTheme::getInfo($themeid);
                    if ($info['class'] != 2) {
                        $new_admin_theme = $old_admin_theme;
                    } else {
                        if (xarCoreCache::isCached('Mod.Variables.themes', 'admin_theme')) {
                            xarCoreCache::delCached('Mod.Variables.themes', 'admin_theme');
                        }
                        if (!$adminapi->install(['regid' => $themeid])) {
                            $new_admin_theme = $old_admin_theme;
                        }
                    }
                } else {
                    $new_admin_theme = $old_admin_theme;
                }
                // catch null value on first run (2.2.x > 2.3.0)
                if (is_null($new_admin_theme)) {
                    $new_admin_theme = $new_user_theme;
                }
                xarModVars::set('themes', 'admin_theme', $new_admin_theme);
            }
            $return_url = xarController::URL('themes', 'admin', 'view');
            xarController::redirect($return_url, null, $this->getContext());
        }

        // display phase
        $data = [];

        $this->var()->check(
            'startnum',
            $data['startnum'],
            'int:1:',
            1
        );

        $this->var()->check(
            'tab',
            $data['tab'],
            'pre:trim:lower:enum:plain:preview',
            null
        );
        $this->var()->check(
            'state',
            $data['state'],
            'int',
            null
        );
        $this->var()->check(
            'class',
            $data['class'],
            'int:0:4', // 0=system, 1=utility, 2=user, 3=all
            null
        );
        $this->var()->check(
            'sort',
            $data['sort'],
            'pre:trim:upper:enum:ASC:DESC',
            'ASC'
        );

        if (!isset($data['tab'])) {
            $data['tab'] = xarModUserVars::get('themes', 'selstyle');
        }
        if (!isset($data['state'])) {
            $data['state'] = xarModUserVars::get('themes', 'selfilter');
        }
        if (!isset($data['class'])) {
            $data['class'] = xarModUserVars::get('themes', 'selclass');
        }
        // support legacy use of class name instead of id (2.2.x > 2.3.0)
        if (isset($data['class']) && (!is_numeric($data['class']) && is_string($data['class']))) {
            $data['class'] = strtr($data['class'], ['system' => 0, 'utility' => 1, 'user' => 2, 'all' => 3]);
        }

        $data['items_per_page'] = xarModVars::get('themes', 'items_per_page');

        $authid = xarSec::genAuthKey();
        $themes = $adminapi->getitems([
            'state' => $data['state'],
            'class' => $data['class'],
            'startnum' => $data['startnum'],
            'numitems' => $data['items_per_page'],
            'sort' => 'name ' . $data['sort'],
        ]);

        $data['total'] = $adminapi->countitems([
            'state' => $data['state'],
            'class' => $data['class'],
        ]);

        $data['user_theme'] = xarModVars::get('themes', 'default_theme');
        $data['admin_theme'] = xarModVars::get('themes', 'admin_theme');

        foreach ($themes as $key => $theme) {
            $theme['info_url'] = xarController::URL(
                'themes',
                'admin',
                'themesinfo',
                ['id' => $theme['regid']]
            );
            $return_url = xarServer::getCurrentURL(['state' => $data['state'] != xarTheme::STATE_ANY ? xarTheme::STATE_ANY : null], false, $theme['name']);
            $return_url = urlencode($return_url);
            switch ($theme['state']) {
                case xarTheme::STATE_UNINITIALISED: // 1
                    if ($theme['class'] != 4) {
                        $theme['init_url'] = xarController::URL(
                            'themes',
                            'admin',
                            'install',
                            ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                        );
                    }
                    break;
                case xarTheme::STATE_INACTIVE: // 2
                    $theme['activate_url'] = xarController::URL(
                        'themes',
                        'admin',
                        'activate',
                        ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    $theme['remove_url'] = xarController::URL(
                        'themes',
                        'admin',
                        'remove',
                        ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarTheme::STATE_ACTIVE: // 3
                    if ($theme['name'] != $data['user_theme'] && $theme['name'] != $data['admin_theme']) {
                        $theme['deactivate_url'] = xarController::URL(
                            'themes',
                            'admin',
                            'deactivate',
                            ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                        );
                    }
                    break;
                case xarTheme::STATE_UPGRADED: // 5
                    $theme['upgrade_url'] = xarController::URL(
                        'themes',
                        'admin',
                        'upgrade',
                        ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;
                case xarTheme::STATE_MISSING_FROM_UNINITIALISED: // 4
                case xarTheme::STATE_MISSING_FROM_INACTIVE: // 7
                case xarTheme::STATE_MISSING_FROM_ACTIVE: // 8
                case xarTheme::STATE_MISSING_FROM_UPGRADED: // 9
                    $theme['remove_url'] = xarController::URL(
                        'themes',
                        'admin',
                        'remove',
                        ['id' => $theme['regid'], 'authid' => $authid, 'return_url' => $return_url]
                    );
                    break;

            }
            // See includes/admin-list-preview
            if ($data['tab'] == 'preview') {
                $theme['preview_img'] = false;
                $preview_img = 'themes/' . $theme['directory'] . '/images/preview.jpg';
                if (is_file($preview_img)) {
                    $theme['preview_img'] = $preview_img;
                }
            }
            $themes[$key] = $theme;
        }

        $data['themes'] = $themes;
        $data['useicons'] = xarModVars::get('themes', 'use_module_icons');
        $data['authid'] = $authid;

        $data['states'] = [
            xarTheme::STATE_ANY =>
                ['id' => xarTheme::STATE_ANY, 'name' => xarML('All')],
            xarTheme::STATE_INSTALLED =>
                ['id' => xarTheme::STATE_INSTALLED, 'name' => xarML('Installed')],
            xarTheme::STATE_ACTIVE =>
                ['id' => xarTheme::STATE_ACTIVE, 'name' => xarML('Active')],
            xarTheme::STATE_INACTIVE =>
                ['id' => xarTheme::STATE_INACTIVE, 'name' => xarML('Inactive')],
            xarTheme::STATE_UNINITIALISED =>
                ['id' => xarTheme::STATE_UNINITIALISED, 'name' => xarML('Uninitialized')],
            xarTheme::STATE_MISSING_FROM_UNINITIALISED =>
                ['id' => xarTheme::STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Inited)')],
            xarTheme::STATE_MISSING_FROM_INACTIVE =>
                ['id' => xarTheme::STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')],
            xarTheme::STATE_MISSING_FROM_ACTIVE =>
                ['id' => xarTheme::STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')],
            xarTheme::STATE_MISSING_FROM_UPGRADED =>
                ['id' => xarTheme::STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')],
        ];

        $data['classes'] = [
            3 => ['id' => 3, 'name' => xarML('All')],
            0 => ['id' => 0, 'name' => xarML('System')],
            1 => ['id' => 1, 'name' => xarML('Utility')],
            2 => ['id' => 2, 'name' => xarML('User')],
            4 => ['id' => 4, 'name' => xarML('Core')],
        ];

        $data['tabs'] = [
            ['id' => 'plain', 'name' => xarML('Plain')],
            ['id' => 'preview', 'name' => xarML('Preview')],
        ];

        // remember filter selections for current user
        xarModUserVars::set('themes', 'selstyle', $data['tab']);
        xarModUserVars::set('themes', 'selfilter', $data['state']);
        xarModUserVars::set('themes', 'selclass', $data['class']);

        $count = count($themes);
        if ($data['state'] == xarTheme::STATE_ANY) {
            if ($data['class'] == 3) {
                $searched = xarML('Showing #(1) themes', $count);
            } else {
                $searched = xarML('Showing #(1) #(2) class themes', $count, $data['classes'][$data['class']]['name']);
            }
        } else {
            if ($data['class'] == 3) {
                $searched = xarML('Showing #(1) themes in #(2) state', $count, $data['states'][$data['state']]['name']);
            } else {
                $searched = xarML('Showing #(1) #(2) class themes in #(3) state', $count, $data['classes'][$data['class']]['name'], $data['states'][$data['state']]['name']);
            }
        }
        $data['searched'] = $searched;

        return $data;
    }
}
