<?php
/**
 * Retrieve a list of itemtypes of this module
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * Utility function to retrieve the list of itemtypes of this module (if any).
 * @param array    $args array of optional parameters<br/>
 * @return array the itemtypes of this module and their description *
 */
function blocks_userapi_getitemtypes(Array $args=array())
{
    $itemtypes = array();

    if (xarSecurity::check('EditBlocks',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    $name = xarML('Block Types');
    $itemtypes[1] = array('label' => xarVar::prepForDisplay($name),
                          'title' => xarVar::prepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarController::URL('blocks','admin','view_types') : ''
                         );

    $name = xarML('Block Groups');
    $itemtypes[2] = array('label' => xarVar::prepForDisplay($name),
                          'title' => xarVar::prepForDisplay(xarML('Display #(1)',$name)),
                          //'url'   => $showurl ? xarController::URL('blocks','admin','view_groups') : ''
                          'url'   => ''
                         );

    $name = xarML('Block Instances');
    $itemtypes[3] = array('label' => xarVar::prepForDisplay($name),
                          'title' => xarVar::prepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarController::URL('blocks','admin','view_instances') : ''
                         );

    return $itemtypes;
}
