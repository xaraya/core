<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminGui;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * base admin confirmlogout function
 * @extends MethodClass<AdminGui>
 */
class ConfirmlogoutMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Confirm logout from administration system
     * @author Andy Varganov <andyv@xaraya.com>
     * @return array|void Data array for display template.
     * @see AdminGui::confirmlogout()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditBase')) {
            return;
        }

        // Template does it all
        return [];
    }
}
