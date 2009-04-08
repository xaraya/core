<?php
/**
 * Lists available objects defined in DD
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
 * the main user function lists the available objects defined in DD
 *
 */
function dynamicdata_user_main()
{
// Security Check
    if(!xarSecurityCheck('ViewDynamicData')) return;

    $data = xarModAPIFunc('dynamicdata','user','menu');

    if (!xarModAPILoad('dynamicdata','user')) return;

    // get items from the objects table
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');

    $data['items'] = array();
    $mymodid = xarMod::getRegID('dynamicdata');
    foreach ($objects as $itemid => $object) {
        // skip the internal objects
        if ($itemid < 3) continue;
        $module_id = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($module_id != $mymodid) {
            continue;
        }
        // nice(r) URLs
        if ($module_id == $mymodid) {
            $module_id = null;
        }
        $itemtype = $object['itemtype'];
        if ($itemtype == 0) {
            $itemtype = null;
        }
        $label = $object['label'];
        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user','view',
                                                         array('module_id' => $module_id,
                                                               'itemtype' => empty($itemtype) ? null : $itemtype)),
                                 'label'    => $label
                                );
    }

    return $data;
}

?>
