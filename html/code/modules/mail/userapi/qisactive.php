<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\UserApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail userapi qisactive function
 * @extends MethodClass<UserApi>
 */
class QisactiveMethod extends MethodClass
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
     * @see UserApi::qisactive()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($objectid)) {
            return false;
        } // we're lazy
        if (!isset($status)) {
            return false;
        }
        return $status;
    }
}
