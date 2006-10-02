<?php
/**
 * Utility function to pass item field definitions
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to pass item field definitions to whoever
 *
 * @param int $args['itemtype'] item type
 * @param int modid ID of the module
 * @return array containing the item field definitions
 */
function dynamicdata_userapi_getitemfields($args)
{
    extract($args);

    $itemfields = array();
    if (empty($itemtype)) return $itemfields;

    $proptypes = Dynamic_Property_Master::getPropertyTypes();
    if (empty($modid)) $modid = xarModGetIDFromName('dynamicdata');

    $tree = xarModAPIFunc('dynamicdata','user', 'getancestors', array('moduleid' => $modid, 'itemtype' => $itemtype, 'base' => false));
    foreach ($tree as $branch) {
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid'    => $modid,
                                      'itemtype' => $branch['itemtype']));

        foreach ($fields as $name => $info) {
            if (empty($info['label'])) continue;
            if (!empty($proptypes[$info['type']])) {
                $type = $proptypes[$info['type']]['name'];
            } else {
                $type = $info['type'];
            }
            $itemfields[$name] = array('name'  => $name,
                                       'label' => $info['label'],
                                       'type'  => $type);
        }
    }
    return $itemfields;
}

?>
