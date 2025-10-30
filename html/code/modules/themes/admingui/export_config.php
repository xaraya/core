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
        $this->var()->find('itemid', $data['itemid'], 'int', 0);
        $this->var()->find('confirm', $data['confirm'], 'bool', false);

        $data['object'] = $this->data()->getObjectList(['name' => 'themes_configurations']);

        // Security
        if (empty($data['object'])) {
            return $this->ctl()->notFound();
        }
        if (!$data['object']->checkAccess('config')) {
            return $this->ctl()->forbidden($this->ml('Export #(1) is forbidden', $data['object']->label));
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
                            $xml .= "    <$name>" . \xarVarPrep::forDisplay($value);
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
