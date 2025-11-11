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

/**
 * themes admin remove function
 * @extends MethodClass<AdminGui>
 */
class RemoveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a theme
     * Loads theme admin API and calls the remove function
     * to actually perform the removal, then redirects to
     * the list function with a status message and retursn true.
     * @author Marty Vance
     * @access public
     * @param int id $ the theme id
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::remove()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('ManageThemes')) {
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

        // Remove theme
        $removed = $adminapi->remove(['regid' => $id]);
        // throw back
        if (!isset($removed)) {
            return;
        }
        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('themes', 'admin', 'view');
        }
        $this->ctl()->redirect($return_url);
        return true;
    }
}
