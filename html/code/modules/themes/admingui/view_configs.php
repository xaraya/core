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

/**
 * themes admin view_configs function
 * @extends MethodClass<AdminGui>
 */
class ViewConfigsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::viewConfigs()
     */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditThemes')) {
            return;
        }

        $data['object'] = $this->data()->getObjectList(['name' => 'themes_configurations']);

        if (!isset($data['object'])) {
            return;
        }
        if (!$data['object']->checkAccess('view')) {
            return $this->ctl()->forbidden($this->ml('View #(1) is forbidden', $data['object']->label));
        }

        // Count the number of items matching the preset arguments - do this before getItems()
        $data['object']->countItems();

        // Get the selected items using the preset arguments
        $data['object']->getItems();

        return $data;
    }
}
