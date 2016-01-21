<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * create a new dynamic object
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['name'] name of the object to create<br/>
 *        string   $args['label'] label of the object to create<br/>
 *        integer  $args['moduleid'] module id of the object to create<br/>
 *        string   $args['itemtype'] item type of the object to create<br/>
 *        string   $args['urlparam'] URL parameter to use for the item (itemid, exid, aid, ...)<br/>
 *        string   $args['config'] some configuration for the object (free to define and use)<br/>
 *        integer  $args['objectid'] object id of the object to create (for import only)<br/>
 *        integer  $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
 * @return integer object ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createobject(Array $args=array())
{
    $objectid = DataObjectMaster::createObject($args);
    return $objectid;
}
?>
