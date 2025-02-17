<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminGui;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin create function
 * @extends MethodClass<AdminGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\mail
     * @subpackage mail
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/771.html
     * @see AdminGui::create()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AddMail')) {
            return;
        }

        // User requested to create a new mailqueue
        // We have to do 2 things:
        // 1. Create a queue for storage if needed
        // 2. Create a 'record' in the Queue definition object
        // If the first fails for some reason, we do not do the second and return to the edit screen if possible
        return xarMod::guiFunc('dynamicdata', 'admin', 'create', $args);
    }
}
