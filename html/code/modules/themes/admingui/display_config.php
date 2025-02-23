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
use DataObjectFactory;
use xarController;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin display_config function
 * @extends MethodClass<AdminGui>
 */
class DisplayConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::displayConfig()
     */

    public function __invoke(array $args = [])
    {
        $data = [];
        xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED);
        xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED);

        $data['object'] = DataObjectFactory::getObject(['name' => 'themes_configurations']);

        if (!isset($data['object'])) {
            return;
        }
        if (!$data['object']->checkAccess('display')) {
            return xarController::forbidden(xarML('Display #(1) is forbidden', $data['object']->label), $this->getContext());
        }

        $data['object']->getItem(['itemid' => $data['itemid']]);
        return $data;
    }
}
