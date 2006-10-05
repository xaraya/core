<?php
/**
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
 * Wrapper for DataObjectMaster::getObjectInfo
 * 
 * @see  DataObjectMaster::getObjectInfo
 */
function dynamicdata_userapi_getobjectinfo($args)
{
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    return DataObjectMaster::getObjectInfo($args);
}
?>
