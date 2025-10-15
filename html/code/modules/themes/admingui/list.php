<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin list function
 * @extends MethodClass<AdminGui>
 */
class ListMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List themes and current settings
     * @author Marty Vance
     * @author Chris Powis <crisp@xaraya.com>
     * @return array|bool data for the template display
     * @see AdminGui::list()
     */
    public function __invoke(array $args = [])
    {
        $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view'));
        return true;
    }
}
