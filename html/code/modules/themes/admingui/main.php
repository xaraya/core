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

/**
 * themes admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Main entry point for the admin interface of this module
     * This function is the default function for the admin interface, and is called whenever the module is
     * initiated with only an admin type but no func parameter passed.
     * The function displays the module's overview page, or redirects to another page if overviews are disabled.
     * @return mixed output display string or boolean true if redirected
     * @author Marty Vance
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditThemes')) {
            return;
        }

        $samemodule = $this->req()->isSameReferer();

        if (!$this->mod()->disableOverview() || $samemodule) {
            $data = [];
            return $this->render('overview', $data);
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view'));
            return true;
        }
    }
}
