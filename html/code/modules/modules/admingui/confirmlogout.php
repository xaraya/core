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
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin confirmlogout function
 * @extends MethodClass<AdminGui>
 */
class ConfirmlogoutMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Confirm logout from administration system
     * @author Andy Varganov <andyv@xaraya.com>
     * @access public
     * @return array|void data for the template display
     * @see AdminGui::confirmlogout()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Template does it all
        return [];
    }
}
