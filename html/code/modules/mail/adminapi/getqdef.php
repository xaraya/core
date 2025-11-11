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

/**
 * mail adminapi getqdef function
 * @extends MethodClass<AdminApi>
 */
class GetqdefMethod extends MethodClass
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
     * @see AdminApi::getqdef()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $qDef = $this->mod()->getVar('queue-definition');
        if ($qDef != null) {
            // Modvar has a value, fetch the info
            $qdefInfo = $this->data()->getObjectInfo(['name' => $qDef]);
            if (isset($qdefInfo)) {
                return $qdefInfo;
            }
        }
        return false;
    }
}
