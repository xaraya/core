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
use ixarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin install function
 * @extends MethodClass<AdminGui>
 */
class InstallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installs a theme
     * Loads module themes API and calls the initialise
     * function to actually perform the initialisation,
     * then redirects to the list function with a
     * status message and returns true.
     * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
     * @param int id the module id to initialise
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::install()
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
        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }
        $this->var()->find(
            'return_url',
            $return_url,
            'pre:trim:str:1:',
            ''
        );

        $minfo = $this->theme()->getInfo($id);
        if (!$adminapi->install(['regid' => $id])) {
            return;
        }

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('themes', 'admin', 'view', ['state' => ixarTheme::STATE_ANY], null) . '#' . $target;
        }

        $this->ctl()->redirect($return_url);
        return true;
    }
}
