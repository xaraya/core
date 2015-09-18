<?php
/**
 * Utility function to pass individual item links to whoever
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args ['itemtype'] item type (optional)<br/>
 *        array    $args ['itemids'] array of item ids to get
 * @return array the itemlink(s) for the item(s).
 */
function roles_userapi_getitemlinks(Array $args=array())
{
    $itemlinks = array();
    if (!xarSecurityCheck('ViewRoles', 0)) {
        return $itemlinks;
    }

    foreach ($args['itemids'] as $itemid) {
        $item = xarMod::apiFunc('roles', 'user', 'get',
            array('id' => $itemid));
        if (!isset($item)) return;
        $itemlinks[$itemid] = array('url' => xarModURL('roles', 'user', 'display',
                array('id' => $itemid)),
            'title' => xarML('Display User'),
            'label' => xarVarPrepForDisplay($item['name']));
    }
    return $itemlinks;
}

?>