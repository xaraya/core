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
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        // Security and sanity checks
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        $this->var()->find('id', $defaulttheme, 'int:1:', 0);
        if (empty($defaulttheme)) {
            return $this->ctl()->notFound();
        }


        $whatwasbefore = $this->mod()->getVar('default_theme');

        if (!isset($defaulttheme)) {
            $defaulttheme = $whatwasbefore;
        }

        $themeInfo = $this->theme()->getInfo($defaulttheme);

        if ($themeInfo['class'] != 2) {
            $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'modifyconfig'));
            return true;
        }

        if ($this->mem()->has('Mod.Variables.themes', 'default_theme')) {
            $this->mem()->del('Mod.Variables.themes', 'default_theme');
        }

        //update the database - activate the theme
        if (!$adminapi->install(['regid' => $defaulttheme])) {
            $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'modifyconfig'));
            return true;
        }

        // update the data
        $this->tpl()->setThemeDir($themeInfo['directory']);
        $this->mod()->setVar('default_theme', $themeInfo['directory']);

        // set the target location (anchor) to go to within the page
        $target = $themeInfo['name'];
        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'themes',
            'admin',
            'view',
            ['state' => 0],
            null
        )) . '#' . $target;
        return true;
    }
}
