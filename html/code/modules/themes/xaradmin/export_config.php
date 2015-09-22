<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 * @todo move the xml generate code to a template based system.
 */

sys::import('modules.dynamicdata.class.objects.master');
    
function themes_admin_export_config()
{
    if (!xarVarFetch('itemid' ,    'int',    $data['itemid'] , 0 ,          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObjectList(array('name' => 'themes_configurations'));

    // Security
    if (empty($data['object']))
        return xarResponse::NotFound();
    if (!$data['object']->checkAccess('config'))
        return xarResponse::Forbidden(xarML('Export #(1) is forbidden', $data['object']->label));

    $where = "theme_id = " . $data['itemid'];
    $items = $data['object']->getItems(array('where' => $where));

    $xml = '';
    if (!empty($items)) {
        $xml .= "<items>\n";
        foreach ($items as $itemid => $item) {
            $xml .= '  <themes_configurations itemid="'.$itemid.'">'."\n";
            foreach ($item as $name => $value) {
                if (isset($item[$name])) {
                    if ($name == 'configuration') {
                    // don't replace anything in the serialized value
                        $xml .= "    <$name>" . $value;
                    } else {
                        $xml .= "    <$name>" . xarVarPrepForDisplay($value);
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

?>