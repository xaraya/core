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
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin regenerate function
 * @extends MethodClass<AdminGui>
 */
class RegenerateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Regenerate list of available modules
     * Loads module admin API and calls the regenerate function
     * to actually perform the regeneration, then redirects
     * to the list function with a status meessage and returns true.
     * @author Xaraya Development Team
     * @access public
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::regenerate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // Regenerate modules
        $regenerated = $adminapi->regenerate();

        if (!isset($regenerated)) {
            return;
        }

        // Redirect
        $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'list'));

        return true;
    }
}
