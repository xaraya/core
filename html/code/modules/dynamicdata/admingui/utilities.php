<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use xarController;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin utilities function
 * @extends MethodClass<AdminGui>
 */
class UtilitiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utilities
     * @see AdminGui::utilities()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        extract($args);
        $data ??= [];
        if (!$this->var()->find('q', $data['option'], 'str', 'query')) {
            return;
        }
        $this->tpl()->setPageTitle($this->var()->prep($this->ml($data['option'])));
        $this->ctl()->redirect($this->mod()->getURL('admin', 'import'));
        return true;
    }
}
