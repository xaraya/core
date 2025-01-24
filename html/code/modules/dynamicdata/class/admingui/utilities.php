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

use Xaraya\DataObject\MethodClass;
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
