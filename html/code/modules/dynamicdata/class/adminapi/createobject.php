<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminApi;
use DataObjectFactory;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata adminapi createobject function
 * @extends MethodClass<AdminApi>
 */
class CreateobjectMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * create a new dynamic object
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['name'] name of the object to create<br/>
     * string   $args['label'] label of the object to create<br/>
     * integer  $args['moduleid'] module id of the object to create<br/>
     * string   $args['itemtype'] item type of the object to create<br/>
     * string   $args['urlparam'] URL parameter to use for the item (itemid, exid, aid, ...)<br/>
     * string   $args['config'] some configuration for the object (free to define and use)<br/>
     * integer  $args['objectid'] object id of the object to create (for import only)<br/>
     * integer  $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     * @return int object ID on success, null on failure
     */
    public function __invoke(array $args = [])
    {
        $objectid = DataObjectFactory::createObject($args);
        return $objectid;
    }
}
