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
 * themes admin export_config function
 * @extends MethodClass<AdminGui>
 */
class ExportConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::exportConfig()
     */

    public function __invoke(array $args = [])
    {
        $data = [];
        xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED);
        xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED);

        $data['object'] = DataObjectFactory::getObjectList(['name' => 'themes_configurations']);

        // Security
        if (empty($data['object'])) {
            return xarController::notFound(null, $this->getContext());
        }
        if (!$data['object']->checkAccess('config')) {
            return xarController::forbidden(xarML('Export #(1) is forbidden', $data['object']->label), $this->getContext());
        }

        $where = "theme_id = " . $data['itemid'];
        $items = $data['object']->getItems(['where' => $where]);

        $xml = '';
        if (!empty($items)) {
            $xml .= "<items>\n";
            foreach ($items as $itemid => $item) {
                $xml .= '  <themes_configurations itemid="' . $itemid . '">' . "\n";
                foreach ($item as $name => $value) {
                    if (isset($item[$name])) {
                        if ($name == 'configuration') {
                            // don't replace anything in the serialized value
                            $xml .= "    <$name>" . $value;
                        } else {
                            $xml .= "    <$name>" . xarVar::prepForDisplay($value);
                        }
                    } else {
                        $xml .= "    <$name>";
                    }
                    $xml .= "</$name>\n";
                }
                $xml .= "  </themes_configurations>\n";
            }
            $xml .= "</items>\n";
        }
        $data['xml'] = $xml;

        return $data;
    }
}
