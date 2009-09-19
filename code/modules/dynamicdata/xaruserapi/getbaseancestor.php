<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Wrapper for DataObjectMaster::getBaseAncestor
 *
 * @see  DataObjectMaster::getBaseAncestor
 * @todo remove this wrapper
 */
function &dynamicdata_userapi_getbaseancestor($args)
{
    $object = DataObjectMaster::getObject($args);
    return $object->getBaseAncestor();
}
?>
