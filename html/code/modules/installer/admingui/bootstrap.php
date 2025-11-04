<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Exception;
use ixarTheme;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin bootstrap function
 * @extends MethodClass<AdminGui>
 */
class BootstrapMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Bootstrap Xaraya
     * @access private
     * @see AdminGui::bootstrap()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');
        $this->mem()->set('installer', 'installing', true);

        # --------------------------------------------------------
        # Create DD configuration and sample objects
        #
        $objects = [
            'configurations',
            'sample',
            'dynamicdata_tablefields',
            'module_settings',
        ];

        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'dynamicdata', 'objects' => $objects])) {
            return;
        }
        # --------------------------------------------------------
        # Create wrapper DD overlay objects for the modules and roles modules
        #
        $objects = [
            'modules',
            //                   'modules_hooks',
            //                   'modules_modvars',
        ];
        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'modules', 'objects' => $objects])) {
            return;
        }

        $objects = [
            //                   'roles_roles',
            'roles_users',
            'roles_groups',
            'roles_user_settings',
        ];

        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'roles', 'objects' => $objects])) {
            return;
        }

        $objects = [
            'themes',
            'themes_configurations',
            'themes_user_settings',
            'themes_jslibraries',
            'themes_csslibraries',
        ];
        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'themes', 'objects' => $objects])) {
            return;
        }

        $objects = [
            'categories',
            'categories_linkages',
        ];
        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'categories', 'objects' => $objects])) {
            return;
        }

        $objects = [
            'privileges_baseprivileges',
            'privileges_privileges',
        ];

        if (!$this->mod()->apiFunc('modules', 'admin', 'standardinstall', ['module' => 'privileges', 'objects' => $objects])) {
            return;
        }

        # --------------------------------------------------------
        # Set up the standard module variables for the core modules
        # Never use createItem with modvar storage. Instead, you update itemid == 0
        #
        $modules = [
            'authsystem',
            'blocks',
            'base',
            'categories',
            'dynamicdata',
            'mail',
            'modules',
            'privileges',
            'roles',
            'themes',
        ];

        foreach ($modules as $module) {
            $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => $module]);
            $data['module_settings']->initialize();
        }

        $modlist = ['roles'];
        foreach ($modlist as $mod) {
            $regid = $this->mod()->getRegID($mod);
            if (!$this->mod()->apiFunc(
                'modules',
                'admin',
                'activate',
                ['regid' => $regid]
            )) {
                throw new Exception("activation of $regid failed");
            }//return;
        }

        // load modules into *_modules table
        if (!$this->mod()->apiFunc('modules', 'admin', 'regenerate')) {
            return;
        }

        // load themes into *_themes table
        if (!$this->mod()->apiFunc('themes', 'admin', 'regenerate')) {
            throw new Exception("themes regeneration failed");
        }

        // Set the state and activate the following themes
        $themelist = ['print','rss','default'];
        foreach ($themelist as $theme) {
            // Set state to inactive
            $regid = xarTheme::getIDFromName($theme);
            if (isset($regid)) {
                if (!$this->mod()->apiFunc('themes', 'admin', 'setstate', ['regid' => $regid,'state' => ixarTheme::STATE_INACTIVE])) {
                    throw new Exception("Setting state of theme with regid: $regid failed");
                }
                // Activate the theme
                if (!$this->mod()->apiFunc('themes', 'admin', 'activate', ['regid' => $regid])) {
                    throw new Exception("Activation of theme with regid: $regid failed");
                }
            }
        }

        $this->ctl()->redirect($this->ctl()->getModuleURL('installer', 'admin', 'create_administrator', ['install_language' => $install_language]));
        return true;
    }
}
