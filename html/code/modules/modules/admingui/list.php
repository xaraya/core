<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;

/**
 * modules admin list function
 * @extends MethodClass<AdminGui>
 */
class ListMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List modules and current settings
     * @author Xaraya Development Team
     * @param array several params from the associated form in template
     * @todo finish cleanup, styles, filters and sort orders
     * @return array|bool data for the template display
     * @see AdminGui::list()
     */
    public function __invoke(array $args = [])
    {
        $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'view'));
        return true;
    }
}
