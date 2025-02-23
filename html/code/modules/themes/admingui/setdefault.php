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
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarTheme;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin setdefault function
 * @extends MethodClass<AdminGui>
 */
class SetdefaultMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Default theme for site
     * Sets the module var for the default site theme.
     * @author Marty Vance
     * @param int id the theme id to set
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::setdefault()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // Security and sanity checks
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $this->var()->find('id', $defaulttheme, 'int:1:', 0);
        if (empty($defaulttheme)) {
            return xarController::notFound(null, $this->getContext());
        }


        $whatwasbefore = xarModVars::get('themes', 'default_theme');

        if (!isset($defaulttheme)) {
            $defaulttheme = $whatwasbefore;
        }

        $themeInfo = xarTheme::getInfo($defaulttheme);

        if ($themeInfo['class'] != 2) {
            xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'), null, $this->getContext());
        }

        if (xarVar::isCached('Mod.Variables.themes', 'default_theme')) {
            xarVar::delCached('Mod.Variables.themes', 'default_theme');
        }

        //update the database - activate the theme
        if (!$adminapi->install(['regid' => $defaulttheme])) {
            xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'), null, $this->getContext());
        }

        // update the data
        xarTpl::setThemeDir($themeInfo['directory']);
        xarModVars::set('themes', 'default_theme', $themeInfo['directory']);

        // set the target location (anchor) to go to within the page
        $target = $themeInfo['name'];
        xarController::redirect(xarController::URL(
            'themes',
            'admin',
            'view',
            ['state' => 0],
            null,
            $target
        ), null, $this->getContext());
        return true;
    }
}
