<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use xarController;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin utilities function
 * @extends MethodClass<AdminGui>
 */
class UtilitiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utilities
     * @package modules\dynamicdata
     * @subpackage dynamicdata
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/182.html
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditDynamicData')) {
            return;
        }

        extract($args);
        $data ??= [];
        if (!$this->var()->fetch('q', 'str', $data['option'], 'query', xarVar::NOT_REQUIRED)) {
            return;
        }
        xarTpl::setPageTitle($this->var()->prep($this->ml($data['option'])));
        $this->ctl()->redirect(xarController::URL('dynamicdata', 'admin', 'import'));
        return true;
    }
}
