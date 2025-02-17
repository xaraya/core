<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;
use xarModHooks;
use sys;

sys::import('xaraya.modules.method');

/**
 * base adminapi waitingcontent function
 * @extends MethodClass<AdminApi>
 */
class WaitingcontentMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Call the waiting content hook
     * @author John Cox <admin@dinerminor.com>
     * @return string[] Array containing output and message.
     * @see AdminApi::waitingcontent()
     */
    public function __invoke(array $args = [])
    {

        // Hooks (we specify that we want the ones for adminpanels here)
        $output = [];
        $output = xarModHooks::call('item', 'waitingcontent', '', ['module' => 'base']);

        if (empty($output)) {
            $message = xarML('Waiting Content has not been configured');
        }

        if (empty($message)) {
            $message = '';
        }

        return ['output'   => $output,
            'message'  => $message];
    }
}
