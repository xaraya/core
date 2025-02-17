<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use Queue;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi maptoqueue function
 * @extends MethodClass<AdminApi>
 */
class MaptoqueueMethod extends MethodClass
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
     * @see AdminApi::maptoqueue()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($msg_structure)) {
            return;
        }

        sys::import('xaraya.structures.sequences.queue');
        // Test mapping, map em all to the masterq
        $q = new Queue('dd', ['name' => 'masterq']);
        return $q;
    }
}
