<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Installer;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.installer.adminapi');

/**
 * Handle the installer admin GUI
 *
 * @method mixed bootstrap(array $args = []) Bootstrap Xaraya
 * @method mixed cleanup(array $args = []) Installer
 * @method mixed createAdministrator(array $args = []) Create default administrator
 * @method mixed finish(array $args = []) Installer
 * @method mixed phase1(array $args = []) Phase 1: Welcome (Set Language and Locale) Page
 * @method mixed phase2(array $args = []) Phase 2: Accept License Page
 * @method mixed phase3(array $args = []) Phase 3: Check system settings
 * @method mixed phase4(array $args = []) Phase 4: Database Settings Page
 * @method mixed phase5(array $args = []) Phase 5: Pre-Boot, Modify Configuration
 * @method mixed security(array $args = []) Installer
 * @method mixed upgrade(array $args = [])
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    public function configure()
    {
        $this->setModType('admin');
        // don't call xarMod:load() for xarInstall::func()
    }
}
