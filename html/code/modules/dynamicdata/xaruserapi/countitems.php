<?php
/**
 * Count number of items held by this module
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
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param array $args the usual suspects :)
 * @return integer number of items held by this module
 */
function dynamicdata_userapi_countitems($args)
{
    $mylist = & DataObjectMaster::getObjectList($args);
    if (!isset($mylist)) return;

    return $mylist->countItems();
}

?>
